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

            //add_action( 'init', array( $this, 'register_post_types' ), 5 );
            //add_filter( 'super_shortcodes_after_form_elements_filter', array( $this, 'add_stripe_element' ), 10, 2 );
            add_action( 'wp_head', array( $this, 'stripe_ipn'));
            if ( $this->is_request( 'admin' ) ) {
                add_filter( 'super_create_form_tabs', array( $this, 'add_tab' ), 10, 1 );
                add_action( 'super_create_form_stripe_tab', array( $this, 'add_tab_content' ) );

                // tmp add_filter( 'manage_super_stripe_txn_posts_columns', array( $this, 'super_stripe_txn_columns' ), 999999 );
                // tmp add_action( 'manage_super_stripe_txn_posts_custom_column', array( $this, 'super_custom_columns' ), 10, 2 );
                // tmp add_action( 'manage_super_stripe_sub_posts_custom_column', array( $this, 'super_custom_columns' ), 10, 2 );

                //add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
                add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );

                //add_action( 'current_screen', array( $this, 'after_screen' ), 0 );
                //add_filter( 'post_row_actions', array( $this, 'remove_row_actions' ), 10, 1 );
                
                add_action( 'after_contact_entry_metabox_hook', array( $this, 'add_transaction_link' ), 0 );

                // tmp add_filter( 'views_edit-super_stripe_txn', array( $this, 'delete_list_views_filter' ), 10, 1 );
            }

            //add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
            //add_filter( 'super_redirect_url_filter', array( $this, 'stripe_redirect' ), 10, 2 );
            //add_action( 'super_front_end_posting_after_insert_post_action', array( $this, 'save_post_id' ) );
            //add_action( 'super_after_wp_insert_user_action', array( $this, 'save_user_id' ) );
            
            add_action( 'super_stripe_webhook_payment_intent_succeeded', array( $this, 'payment_intent_succeeded' ), 10 );
            //add_action( 'super_stripe_webhook_payment_intent_created', array( $this, 'payment_intent_created' ), 10 );
            //add_action( 'super_stripe_webhook_payment_intent_payment_failed', array( $this, 'payment_intent_payment_failed' ), 10 );
            
            // Load more Transactions, Products, Customers
            // tmp add_action( 'wp_ajax_super_stripe_api_handler', array( $this, 'super_stripe_api_handler' ) );

            // Prepare payment
            // tmp add_action( 'wp_ajax_super_stripe_prepare_payment', array( $this, 'stripe_prepare_payment' ) );
            // tmp add_action( 'wp_ajax_nopriv_super_stripe_prepare_payment', array( $this, 'stripe_prepare_payment' ) );

            // Create customer
            // tmp add_action( 'wp_ajax_super_stripe_create_subscription', array( $this, 'stripe_create_subscription' ) );
            // tmp add_action( 'wp_ajax_nopriv_super_stripe_create_subscription', array( $this, 'stripe_create_subscription' ) );
            

            // Filters since 1.2.3
            // tmp add_filter( 'super_common_js_dynamic_functions_filter', array( $this, 'add_dynamic_function' ), 100, 2 );

            //add_filter( 'super_form_styles_filter', array( $this, 'add_stripe_styles' ), 100, 2 );
            //add_filter( 'super_enqueue_scripts', array( $this, 'super_enqueue_scripts' ), 10, 1 );
            //add_filter( 'super_enqueue_styles', array( $this, 'super_enqueue_styles' ), 10, 1 );

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
        public static function handle_webhooks($wp){
            if ( array_key_exists( 'sfssidr', $wp->query_vars ) ) {
                // Retry URL (from async failed email)
                self::setAppInfo();
                $s = \Stripe\Checkout\Session::retrieve($wp->query_vars['sfssidr'], []);
                $submissionInfo = get_option( '_sfsi_' . $s['metadata']['sfsi_id'], array() );
                $checkout_session = \Stripe\Checkout\Session::create($submissionInfo['stripeData']);
                wp_redirect($checkout_session->url);
                exit;
            }
            if ( array_key_exists( 'sfssids', $wp->query_vars ) ) {
                // Success URL
                error_log('Returned from Stripe Checkout session via success URL');
                // Do things
                self::setAppInfo();
                $s = \Stripe\Checkout\Session::retrieve($wp->query_vars['sfssids'], []);
                $m = $s['metadata'];
                //$submissionInfo = get_option( '_sfsi_' . $m['sfsi_id'], array() );
                // Now redirect to success URL without checkout session ID parameter
                $url = SUPER_Common::getClientData('stripe_home_success_url_'.$m['sf_id']);
                if($url===false){
                    wp_redirect(home_url());
                    exit;
                }
                wp_redirect(remove_query_arg(array('sfssid'), $url));
                exit;
            }
            if ( array_key_exists( 'sfssidc', $wp->query_vars ) ) {
                // Cancel URL
                error_log('Returned from Stripe Checkout session via cancel URL');
                // Do things
                self::setAppInfo();
                $s = \Stripe\Checkout\Session::retrieve($wp->query_vars['sfssidc'], []);
                $m = $s['metadata'];
                // Get form submission info
                $submissionInfo = get_option( '_sfsi_' . $m['sfsi_id'], array() );
                SUPER_Common::cleanupFormSubmissionInfo($m['sfsi_id'], 'stripe'); // stored in `wp_options` table as sfsi_%
                // Now redirect to cancel URL without checkout session ID parameter
                $submissionInfo['stripe_home_cancel_url'] = remove_query_arg(array('sfssid'), $submissionInfo['stripe_home_cancel_url'] );
                $submissionInfo['stripe_home_cancel_url'] = remove_query_arg(array('sfr'), $submissionInfo['stripe_home_cancel_url'] );
                if($submissionInfo){
                    if(!empty($submissionInfo['referer'])){
                        $url = add_query_arg('sfr', $m['sfsi_id'], $submissionInfo['referer']);
                        wp_redirect($url);
                        exit;
                    }else{
                        wp_redirect(home_url());
                        exit;
                    }
                }
                // Redirect to home URL instead
                error_log('Redirect to home URL instead');
                wp_redirect(add_query_arg('sfr', $m['sfsi_id'], $submissionInfo['stripe_home_cancel_url']));
                exit;
            }
            if ( array_key_exists( 'sfstripewebhook', $wp->query_vars ) ) {
                if($wp->query_vars['sfstripewebhook']==='true'){
                    // Success URL
                    // Set your secret key. Remember to switch to your live secret key in production.
                    // See your keys here: https://dashboard.stripe.com/apikeys
                    self::setAppInfo();
                    // You can find your endpoint's secret in your webhook settings
                    $global_settings = SUPER_Common::get_global_settings();
                    if(!empty($global_settings['stripe_mode']) ) {
                        $global_settings['stripe_mode'] = 'sandbox';
                    }else{
                        $global_settings['stripe_mode'] = 'live';
                    }
                    $endpoint_secret = $global_settings['stripe_' . $global_settings['stripe_mode'] . '_webhook_secret']; // e.g: whsec_XXXXXXX
                    $payload = @file_get_contents('php://input');
                    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
                    $event = null;
                    try {
                        $event = \Stripe\Webhook::constructEvent( $payload, $sig_header, $endpoint_secret );
                    } catch(\UnexpectedValueException $e) {
                        // Invalid payload
                        http_response_code(400);
                        exit();
                    } catch(\Stripe\Exception\SignatureVerificationException $e) {
                        // Invalid signature
                        http_response_code(400);
                        exit();
                    }
                    // Handle the checkout.session.completed event
                    error_log('Stripe webhook event type: ' . $event->type);
                    error_log('Event object: ' . $event->data->object);
                    switch ($event->type) {
                        // Events for subscriptions
                        case 'customer.subscription.created':
                            error_log('Subscription was created');
                            // Sent when the subscription is created. 
                            // The subscription status might be incomplete if customer authentication is required to complete the payment or if you set payment_behavior to default_incomplete.
                            // View subscription payment behavior to learn more.
                            break;
                        case 'customer.subscription.updated':
                            // Sent when the subscription is successfully started, after the payment is confirmed. 
                            // Also sent whenever a subscription is changed. For example, adding a coupon, applying a discount, adding an invoice item, and changing plans all trigger this event.
                            error_log('Subscription was updated');
                            $subscription = $event->data->object;
                            $m = $subscription->metadata;
                            error_log(json_encode($m));
                            //$submissionInfo = get_option( '_sfsi_' . $m['sfsi_id'], array() );
                            // Get form settings
                            $settings = SUPER_Common::get_form_settings($m->sf_id);
                            $s = SUPER_Stripe::get_default_stripe_settings($settings);
                            // $m['sf_entry']
                            // $m['sf_user']
                            // $m['sf_post']
                            error_log('subscription id: ' . $subscription->id);
                            error_log('subscription object: ' . json_encode($subscription));
                            if(!empty($m['sf_entry'])){
                                // Maybe update entry status?
                                $statuses = explode("\n", $s['subscription']['entry_status']);
                                foreach( $statuses as $v ) {
                                    $v = explode('|', $v);
                                    $v[0] = trim($v[0]);
                                    $subscription_status = $v[0]; // `active`, `paused`, `canceled`
                                    // Check if this matches the current subscription status
                                    if($subscription_status===$subscription->status){
                                        $entry_status = (isset($v[1]) ? trim($v[1]) : '');
                                        if($entry_status==='') continue; // skip if empty
                                        error_log('Stripe subscription was updated, change entry #'.$m['sf_entry'].' status to: '.$entry_status);
                                        update_post_meta( $m['sf_entry'], '_super_contact_entry_status', $entry_status );
                                    }
                                }
                            }
                            if(!empty($m['sf_post'])){
                                // Maybe update post status?
                            }
                            if(!empty($m['sf_user'])){
                                // Maybe update user role or user login status?
                            }

                            //if(empty($s['subscription'])) {
                            //    $s['subscription'] = array(
                            //        'entry_status' => "active|active\npaused|pending\ncanceled|trash",
                            //        'post_status' => "active|publish\npaused|pending\ncanceled|trash",
                            //        'login_status' => "active|active\npaused|blocked\ncanceled|blocked",
                            //        'user_role' => "active|subscriber\npaused|customer\ncanceled|customer"

                            break;
                        case 'customer.subscription.deleted':
                            // Sent when a customers subscription ends.
                            error_log('Subscription was deleted');
                            break;
                        case 'invoice.paid':
                            // Sent when the invoice is successfully paid. You can provision access to your product when you receive this event and the subscription status is active.
                            error_log('Invoice has been paid');
                            break;
                        case 'invoice.payment_action_required':
                            // Sent when the invoice requires customer authentication. Learn how to handle the subscription when the invoice requires action.
                            // We let Stripe handle these emails: 
                            // https://dashboard.stripe.com/settings/billing/automatic > Manage failed payments > Customer emails > `Send emails to customers to update failed card payment methods`
                            error_log('Action required from user');
                            break;
                        case 'invoice.payment_failed':
                            // This event is triggered when the payment associated with an invoice fails. 
                            // It indicates that the payment attempt was unsuccessful. 
                            // You should handle this event to update your records, take appropriate actions based on your business logic (such as notifying the customer or canceling the order), 
                            // and provide an alternative payment method or resolve any issues causing the payment failure.
                            error_log('Payment for invoice failed');
                            break;

                        // Events for one-time payments
                        case 'payment_intent.succeeded':
                            error_log('Payment succeeded :)');
                            break;
                        case 'payment_intent.payment_failed':
                            error_log('Payment failed :)');
                            break;
                        case 'checkout.session.completed':
                            // The customer has successfully authorized the 
                            // debit payment by submitting the Checkout form.	
                            // Wait for the payment to succeed or fail.
                            $session = $event->data->object;
                            $submissionInfo = get_option( '_sfsi_' . $session->metadata->sfsi_id, array() );
                            $form_id = $submissionInfo['form_id'];
                            // Get form settings
                            $settings = SUPER_Common::get_form_settings($form_id);
                            $s = SUPER_Stripe::get_default_stripe_settings($settings);
                            SUPER_Common::triggerEvent('stripe.checkout.session.completed', array('form_id'=>$form_id, 'stripe_session'=>$session));
                            // Check if the order is paid (for example, from a card payment)
                            // A delayed notification payment will have an `unpaid` status, as you're still waiting for funds to be transferred from the customer's account.
                            if($session->payment_status=='paid' || $session->payment_status=='no_payment_required') {
                                error_log('session ID: ' . $session->id);
                                error_log('session before: ' . json_encode($session));
                                //$session = \Stripe\Checkout\Session::retrieve($session->id, array(
                                //    'expand' => array('invoice')
                                //));
                                error_log('session after: ' . json_encode($session));
                                // Update user role, post status, user login status or entry status
                                SUPER_Stripe::fulfillOrder(array('form_id'=>$form_id, 's'=>$s, 'stripe_session'=>$session, 'submissionInfo'=>$submissionInfo));
                                // Fulfill the purchase
                                SUPER_Common::triggerEvent('stripe.fulfill_order', array('form_id'=>$form_id, 'stripe_session'=>$session));
                                // Delete submission info
                                delete_option( '_sfsi_' . $session->metadata->sfsi_id );
                            }
                            break;
                        case 'checkout.session.async_payment_succeeded':
                            // This step is only required if you plan to use any of 
                            // the following payment methods:
                            // Bacs Direct Debit, Boleto, Canadian pre-authorized debits, 
                            // Konbini, OXXO, SEPA Direct Debit, SOFORT, or ACH Direct Debit.
                            // Timings: bacs-debit T+6 (7 days max)
                            // Timings: ...
                            $session = $event->data->object;
                            $submissionInfo = get_option( '_sfsi_' . $session->metadata->sfsi_id, array() );
                            $form_id = $submissionInfo['form_id'];
                            // Get form settings
                            $settings = SUPER_Common::get_form_settings($form_id);
                            $s = SUPER_Stripe::get_default_stripe_settings($settings);
                            SUPER_Common::triggerEvent('stripe.checkout.session.async_payment_succeeded', array('form_id'=>$form_id, 'stripe_session'=>$session));
                            // Update user role, post status, user login status or entry status
                            SUPER_Stripe::fulfillOrder(array('form_id'=>$form_id, 's'=>$s, 'stripe_session'=>$session, 'submissionInfo'=>$submissionInfo));
                            // Fulfill the purchase
                            SUPER_Common::triggerEvent('stripe.fulfill_order', array('form_id'=>$form_id, 'stripe_session'=>$session));
                            // Delete submission info
                            delete_option( '_sfsi_' . $session->metadata->sfsi_id );
                            break;
                        case 'checkout.session.async_payment_failed':
                            // The payment was declined, or failed for some other reason.
                            // Contact the customer via email and request that they 
                            // place a new order.
                            $session = $event->data->object;
                            $submissionInfo = get_option( '_sfsi_' . $session->metadata->sfsi_id, array() );
                            $form_id = $submissionInfo['form_id'];
                            SUPER_Common::triggerEvent('stripe.checkout.session.async_payment_failed', array('form_id'=>$form_id,'stripe_session'=>$session));
                            $to = $session->customer_details->email;
                            $data = $submissionInfo['data'];
                            // Get form settings
                            $settings = SUPER_Common::get_form_settings($form_id);
                            $s = SUPER_Stripe::get_default_stripe_settings($settings);
                            // Send an email to the customer asking them to retry their order
                            $subject = SUPER_Common::email_tags( $s['retryPaymentEmail']['subject'], $data, $settings ); // e.g: Payment failed
                            $body = SUPER_Common::email_tags( $s['retryPaymentEmail']['body'], $data, $settings ); // e.g: 'Payment failed, please retry via the below URL:<br /><br /><a href="' . $retryUrl . '">' . $retryUrl . '</a>';
                            // Replace tag {stripe_retry_payment_expiry} with expiry (amount is in hours e.g: 48)
                            $expiry = SUPER_Common::email_tags( $s['retryPaymentEmail']['expiry'], $data, $settings ); // e.g: 'Payment failed, please retry via the below URL:<br /><br /><a href="' . $retryUrl . '">' . $retryUrl . '</a>';
                            $body = str_replace( '{stripe_retry_payment_expiry}', $expiry, $body );
                            // Replace tag {stripe_retry_payment_url} with URL
                            $domain = home_url(); // e.g: 'http://domain.com';
                            $home_url = trailingslashit($domain);
                            $retryUrl = $home_url . 'sfssid/retry/' . $session->id; //{CHECKOUT_SESSION_ID}';
                            $body = str_replace( '{stripe_retry_payment_url}', $retryUrl, $body );
                            if($s['retryPaymentEmail']['lineBreaks']==='true'){
                                $body = nl2br($body);
                            }
                            $mail = SUPER_Common::email( array( 'to'=>$to, 'subject'=>$subject, 'body'=>$body ));
                            if($mail==false){
                                http_response_code(400);
                            }
                            break;
                        // tmp case 'invoice.created':
                        // tmp     // Attach to entry?
                        // tmp     $invoice = $event->data->object;
                        // tmp     error_log('Invoice Object: '.json_encode($invoice));
                        // tmp     error_log('PDF : '.json_encode($invoice->invoice_pdf));
                        // tmp     error_log('metadata : '.json_encode($invoice->metadata));
                        // tmp     if(!empty($invoice->metadata->sf_entry)){
                        // tmp         $entry_id = absint($invoice->metadata->sf_entry);
                        // tmp         $stripe_connections = get_post_meta($entry_id, '_super_stripe_connections', true);
                        // tmp         if(!is_array($stripe_connections)) $stripe_connections = array();
                        // tmp         error_log('before: ' . json_encode($stripe_connections));
                        // tmp         $stripe_connections['invoice'] = $invoice->id;
                        // tmp         error_log('after: ' . json_encode($stripe_connections));
                        // tmp         update_post_meta($entry_id, '_super_stripe_connections', $stripe_connections);
                        // tmp     }
                        // tmp     // we don't want to store invoices on a server really... $invoice = $event->data->object;
                        // tmp     // we don't want to store invoices on a server really... error_log('Invoice Object: '.json_encode($invoice));
                        // tmp     // we don't want to store invoices on a server really... error_log('PDF : '.json_encode($invoice->invoice_pdf));
                        // tmp     // we don't want to store invoices on a server really... //"invoice": "in_1NHaOKFKn7uROhgCSQF4RVe9",
                        // tmp     // we don't want to store invoices on a server really... //"invoice_creation": {
                        // tmp     // we don't want to store invoices on a server really... //    "enabled": true,
                        // tmp     // we don't want to store invoices on a server really... //    "invoice_data": {
                        // tmp     // we don't want to store invoices on a server really... //        "account_tax_ids": null,
                        // tmp     // we don't want to store invoices on a server really... //        "custom_fields": null,
                        // tmp     // we don't want to store invoices on a server really... //        "description": null,
                        // tmp     // we don't want to store invoices on a server really... //        "footer": null,
                        // tmp     // we don't want to store invoices on a server really... //        "metadata": {
                        // tmp     // we don't want to store invoices on a server really... //            "sf_entry": "68555",
                        // tmp     // we don't want to store invoices on a server really... //            "sf_id": "68300",
                        // tmp     // we don't want to store invoices on a server really... //            "sf_user": "359",
                        // tmp     // we don't want to store invoices on a server really... //            "sfsi_id": "a90ef02e9d16981116228ee62ac609cf.1687732546"
                        // tmp     // we don't want to store invoices on a server really... //        },
                        // tmp     // we don't want to store invoices on a server really... //        "rendering_options": null
                        // tmp     // we don't want to store invoices on a server really... //    }
                        // tmp     // we don't want to store invoices on a server really... //},

                        // tmp     // we don't want to store invoices on a server really... $url = $invoice->invoice_pdf;
                        // tmp     // we don't want to store invoices on a server really... error_log('$url: ' . $url);
                        // tmp     // we don't want to store invoices on a server really... // Use the WordPress HTTP API to download the file
                        // tmp     // we don't want to store invoices on a server really... $response = wp_remote_get($url);
                        // tmp     // we don't want to store invoices on a server really... error_log('$response: ?');
                        // tmp     // we don't want to store invoices on a server really... error_log('Response code: ' . wp_remote_retrieve_response_code($response));
                        // tmp     // we don't want to store invoices on a server really... if(is_wp_error($response)){
                        // tmp     // we don't want to store invoices on a server really...     error_log('$response: ??');
                        // tmp     // we don't want to store invoices on a server really...     $error_message = $response->get_error_message();
                        // tmp     // we don't want to store invoices on a server really...     error_log('Error message: ' . $error_message);
                        // tmp     // we don't want to store invoices on a server really... } 
                        // tmp     // we don't want to store invoices on a server really... error_log('Reached this');
                        // tmp     // we don't want to store invoices on a server really... if(!is_wp_error($response) && wp_remote_retrieve_response_code($response)===200){
                        // tmp     // we don't want to store invoices on a server really...     error_log('was able to get 200 code from stripe for this invoice PDF file');
                        // tmp     // we don't want to store invoices on a server really...     require_once( ABSPATH . 'wp-admin/includes/image.php' );
                        // tmp     // we don't want to store invoices on a server really...     require_once( ABSPATH . 'wp-admin/includes/file.php' );
                        // tmp     // we don't want to store invoices on a server really...     require_once( ABSPATH . 'wp-admin/includes/media.php' );
                        // tmp     // we don't want to store invoices on a server really...     $wp_mime_types = wp_get_mime_types();
                        // tmp     // we don't want to store invoices on a server really...     $mime_types = array( 'ez' => 'application/andrew-inset', 'aw' => 'application/applixware', 'atom' => 'application/atom+xml', 'atomcat' => 'application/atomcat+xml', 'atomsvc' => 'application/atomsvc+xml', 'ccxml' => 'application/ccxml+xml', 'cdmia' => 'application/cdmi-capability', 'cdmic' => 'application/cdmi-container', 'cdmid' => 'application/cdmi-domain', 'cdmio' => 'application/cdmi-object', 'cdmiq' => 'application/cdmi-queue', 'cu' => 'application/cu-seeme', 'davmount' => 'application/davmount+xml', 'dbk' => 'application/docbook+xml', 'dssc' => 'application/dssc+der', 'xdssc' => 'application/dssc+xml', 'ecma' => 'application/ecmascript', 'emma' => 'application/emma+xml', 'epub' => 'application/epub+zip', 'exi' => 'application/exi', 'pfr' => 'application/font-tdpfr', 'gml' => 'application/gml+xml', 'gpx' => 'application/gpx+xml', 'gxf' => 'application/gxf', 'stk' => 'application/hyperstudio', 'ink' => 'application/inkml+xml', 'inkml' => 'application/inkml+xml', 'ipfix' => 'application/ipfix', 'jar' => 'application/java-archive', 'ser' => 'application/java-serialized-object', 'json' => 'application/json', 'jsonml' => 'application/jsonml+json', 'lostxml' => 'application/lost+xml', 'hqx' => 'application/mac-binhex40', 'cpt' => 'application/mac-compactpro', 'mads' => 'application/mads+xml', 'mrc' => 'application/marc', 'mrcx' => 'application/marcxml+xml', 'nb' => 'application/mathematica', 'mathml' => 'application/mathml+xml', 'mbox' => 'application/mbox', 'mscml' => 'application/mediaservercontrol+xml', 'metalink' => 'application/metalink+xml', 'meta4' => 'application/metalink4+xml', 'mets' => 'application/mets+xml', 'mods' => 'application/mods+xml', 'm21' => 'application/mp21', 'mp21' => 'application/mp21', 'mp4s' => 'application/mp4', 'mxf' => 'application/mxf', 'bin' => 'application/octet-stream', 'dms' => 'application/octet-stream', 'lrf' => 'application/octet-stream', 'mar' => 'application/octet-stream', 'so' => 'application/octet-stream', 'dist' => 'application/octet-stream', 'distz' => 'application/octet-stream', 'bpk' => 'application/octet-stream', 'dump' => 'application/octet-stream', 'elc' => 'application/octet-stream', 'deploy' => 'application/octet-stream', 'oda' => 'application/oda', 'opf' => 'application/oebps-package+xml', 'ogx' => 'application/ogg', 'omdoc' => 'application/omdoc+xml', 'xer' => 'application/patch-ops-error+xml', 'pgp' => 'application/pgp-encrypted', 'sig' => 'application/pgp-signature', 'prf' => 'application/pics-rules', 'p10' => 'application/pkcs10', 'p7m' => 'application/pkcs7-mime', 'p7c' => 'application/pkcs7-mime', 'p7s' => 'application/pkcs7-signature', 'p8' => 'application/pkcs8', 'cer' => 'application/pkix-cert', 'crl' => 'application/pkix-crl', 'pkipath' => 'application/pkix-pkipath', 'pki' => 'application/pkixcmp', 'pls' => 'application/pls+xml', 'ai' => 'application/postscript', 'eps' => 'application/postscript', 'cww' => 'application/prs.cww', 'pskcxml' => 'application/pskc+xml', 'rdf' => 'application/rdf+xml', 'rif' => 'application/reginfo+xml', 'rnc' => 'application/relax-ng-compact-syntax', 'rl' => 'application/resource-lists+xml', 'rld' => 'application/resource-lists-diff+xml', 'gbr' => 'application/rpki-ghostbusters', 'mft' => 'application/rpki-manifest', 'roa' => 'application/rpki-roa', 'rsd' => 'application/rsd+xml', 'rss' => 'application/rss+xml', 'sbml' => 'application/sbml+xml', 'scq' => 'application/scvp-cv-request', 'scs' => 'application/scvp-cv-response', 'spq' => 'application/scvp-vp-request', 'spp' => 'application/scvp-vp-response', 'sdp' => 'application/sdp', 'setpay' => 'application/set-payment-initiation', 'setreg' => 'application/set-registration-initiation', 'shf' => 'application/shf+xml', 'smi' => 'application/smil+xml', 'smil' => 'application/smil+xml', 'rq' => 'application/sparql-query', 'srx' => 'application/sparql-results+xml', 'gram' => 'application/srgs', 'grxml' => 'application/srgs+xml', 'sru' => 'application/sru+xml', 'ssdl' => 'application/ssdl+xml', 'ssml' => 'application/ssml+xml', 'tei' => 'application/tei+xml', 'teicorpus' => 'application/tei+xml', 'tfi' => 'application/thraud+xml', 'tsd' => 'application/timestamped-data', 'plb' => 'application/vnd.3gpp.pic-bw-large', 'psb' => 'application/vnd.3gpp.pic-bw-small', 'pvb' => 'application/vnd.3gpp.pic-bw-var', 'tcap' => 'application/vnd.3gpp2.tcap', 'pwn' => 'application/vnd.3m.post-it-notes', 'aso' => 'application/vnd.accpac.simply.aso', 'imp' => 'application/vnd.accpac.simply.imp', 'acu' => 'application/vnd.acucobol', 'atc' => 'application/vnd.acucorp', 'acutc' => 'application/vnd.acucorp', 'air' => 'application/vnd.adobe.air-application-installer-package+zip', 'fcdt' => 'application/vnd.adobe.formscentral.fcdt', 'fxpl' => 'application/vnd.adobe.fxp', 'xdp' => 'application/vnd.adobe.xdp+xml', 'xfdf' => 'application/vnd.adobe.xfdf', 'ahead' => 'application/vnd.ahead.space', 'azf' => 'application/vnd.airzip.filesecure.azf', 'azs' => 'application/vnd.airzip.filesecure.azs', 'azw' => 'application/vnd.amazon.ebook', 'acc' => 'application/vnd.americandynamics.acc', 'ami' => 'application/vnd.amiga.ami', 'apk' => 'application/vnd.android.package-archive', 'cii' => 'application/vnd.anser-web-certificate-issue-initiation', 'fti' => 'application/vnd.anser-web-funds-transfer-initiation', 'atx' => 'application/vnd.antix.game-component', 'mpkg' => 'application/vnd.apple.installer+xml', 'm3u8' => 'application/vnd.apple.mpegurl', 'swi' => 'application/vnd.aristanetworks.swi', 'iota' => 'application/vnd.astraea-software.iota', 'aep' => 'application/vnd.audiograph', 'mpm' => 'application/vnd.blueice.multipass', 'bmi' => 'application/vnd.bmi', 'rep' => 'application/vnd.businessobjects', 'cdxml' => 'application/vnd.chemdraw+xml', 'mmd' => 'application/vnd.chipnuts.karaoke-mmd', 'cdy' => 'application/vnd.cinderella', 'rp9' => 'application/vnd.cloanto.rp9', 'c4g' => 'application/vnd.clonk.c4group', 'c4d' => 'application/vnd.clonk.c4group', 'c4f' => 'application/vnd.clonk.c4group', 'c4p' => 'application/vnd.clonk.c4group', 'c4u' => 'application/vnd.clonk.c4group', 'c11amc' => 'application/vnd.cluetrust.cartomobile-config', 'c11amz' => 'application/vnd.cluetrust.cartomobile-config-pkg', 'csp' => 'application/vnd.commonspace', 'cdbcmsg' => 'application/vnd.contact.cmsg', 'cmc' => 'application/vnd.cosmocaller', 'clkx' => 'application/vnd.crick.clicker', 'clkk' => 'application/vnd.crick.clicker.keyboard', 'clkp' => 'application/vnd.crick.clicker.palette', 'clkt' => 'application/vnd.crick.clicker.template', 'clkw' => 'application/vnd.crick.clicker.wordbank', 'wbs' => 'application/vnd.criticaltools.wbs+xml', 'pml' => 'application/vnd.ctc-posml', 'ppd' => 'application/vnd.cups-ppd', 'car' => 'application/vnd.curl.car', 'pcurl' => 'application/vnd.curl.pcurl', 'dart' => 'application/vnd.dart', 'rdz' => 'application/vnd.data-vision.rdz', 'uvf' => 'application/vnd.dece.data', 'uvvf' => 'application/vnd.dece.data', 'uvd' => 'application/vnd.dece.data', 'uvvd' => 'application/vnd.dece.data', 'uvt' => 'application/vnd.dece.ttml+xml', 'uvvt' => 'application/vnd.dece.ttml+xml', 'uvx' => 'application/vnd.dece.unspecified', 'uvvx' => 'application/vnd.dece.unspecified', 'uvz' => 'application/vnd.dece.zip', 'uvvz' => 'application/vnd.dece.zip', 'fe_launch' => 'application/vnd.denovo.fcselayout-link', 'dna' => 'application/vnd.dna', 'mlp' => 'application/vnd.dolby.mlp', 'dpg' => 'application/vnd.dpgraph', 'dfac' => 'application/vnd.dreamfactory', 'kpxx' => 'application/vnd.ds-keypoint', 'ait' => 'application/vnd.dvb.ait', 'svc' => 'application/vnd.dvb.service', 'geo' => 'application/vnd.dynageo', 'mag' => 'application/vnd.ecowin.chart', 'nml' => 'application/vnd.enliven', 'esf' => 'application/vnd.epson.esf', 'msf' => 'application/vnd.epson.msf', 'qam' => 'application/vnd.epson.quickanime', 'slt' => 'application/vnd.epson.salt', 'ssf' => 'application/vnd.epson.ssf', 'es3' => 'application/vnd.eszigno3+xml', 'et3' => 'application/vnd.eszigno3+xml', 'ez2' => 'application/vnd.ezpix-album', 'ez3' => 'application/vnd.ezpix-package', 'fdf' => 'application/vnd.fdf', 'mseed' => 'application/vnd.fdsn.mseed', 'seed' => 'application/vnd.fdsn.seed', 'dataless' => 'application/vnd.fdsn.seed', 'gph' => 'application/vnd.flographit', 'ftc' => 'application/vnd.fluxtime.clip', 'fm' => 'application/vnd.framemaker', 'frame' => 'application/vnd.framemaker', 'maker' => 'application/vnd.framemaker', 'book' => 'application/vnd.framemaker', 'fnc' => 'application/vnd.frogans.fnc', 'ltf' => 'application/vnd.frogans.ltf', 'fsc' => 'application/vnd.fsc.weblaunch', 'oas' => 'application/vnd.fujitsu.oasys', 'oa2' => 'application/vnd.fujitsu.oasys2', 'oa3' => 'application/vnd.fujitsu.oasys3', 'fg5' => 'application/vnd.fujitsu.oasysgp', 'bh2' => 'application/vnd.fujitsu.oasysprs', 'ddd' => 'application/vnd.fujixerox.ddd', 'xdw' => 'application/vnd.fujixerox.docuworks', 'xbd' => 'application/vnd.fujixerox.docuworks.binder', 'fzs' => 'application/vnd.fuzzysheet', 'txd' => 'application/vnd.genomatix.tuxedo', 'ggb' => 'application/vnd.geogebra.file', 'ggt' => 'application/vnd.geogebra.tool', 'gex' => 'application/vnd.geometry-explorer', 'gre' => 'application/vnd.geometry-explorer', 'gxt' => 'application/vnd.geonext', 'g2w' => 'application/vnd.geoplan', 'g3w' => 'application/vnd.geospace', 'gmx' => 'application/vnd.gmx', 'kml' => 'application/vnd.google-earth.kml+xml', 'kmz' => 'application/vnd.google-earth.kmz', 'gqf' => 'application/vnd.grafeq', 'gqs' => 'application/vnd.grafeq', 'gac' => 'application/vnd.groove-account', 'ghf' => 'application/vnd.groove-help', 'gim' => 'application/vnd.groove-identity-message', 'grv' => 'application/vnd.groove-injector', 'gtm' => 'application/vnd.groove-tool-message', 'tpl' => 'application/vnd.groove-tool-template', 'vcg' => 'application/vnd.groove-vcard', 'hal' => 'application/vnd.hal+xml', 'zmm' => 'application/vnd.handheld-entertainment+xml', 'hbci' => 'application/vnd.hbci', 'les' => 'application/vnd.hhe.lesson-player', 'hpgl' => 'application/vnd.hp-hpgl', 'hpid' => 'application/vnd.hp-hpid', 'hps' => 'application/vnd.hp-hps', 'jlt' => 'application/vnd.hp-jlyt', 'pcl' => 'application/vnd.hp-pcl', 'pclxl' => 'application/vnd.hp-pclxl', 'sfd-hdstx' => 'application/vnd.hydrostatix.sof-data', 'mpy' => 'application/vnd.ibm.minipay', 'afp' => 'application/vnd.ibm.modcap', 'listafp' => 'application/vnd.ibm.modcap', 'list3820' => 'application/vnd.ibm.modcap', 'irm' => 'application/vnd.ibm.rights-management', 'icc' => 'application/vnd.iccprofile', 'icm' => 'application/vnd.iccprofile', 'igl' => 'application/vnd.igloader', 'ivp' => 'application/vnd.immervision-ivp', 'ivu' => 'application/vnd.immervision-ivu', 'igm' => 'application/vnd.insors.igm', 'xpw' => 'application/vnd.intercon.formnet', 'xpx' => 'application/vnd.intercon.formnet', 'i2g' => 'application/vnd.intergeo', 'qbo' => 'application/vnd.intu.qbo', 'qfx' => 'application/vnd.intu.qfx', 'rcprofile' => 'application/vnd.ipunplugged.rcprofile', 'irp' => 'application/vnd.irepository.package+xml', 'xpr' => 'application/vnd.is-xpr', 'fcs' => 'application/vnd.isac.fcs', 'jam' => 'application/vnd.jam', 'rms' => 'application/vnd.jcp.javame.midlet-rms', 'jisp' => 'application/vnd.jisp', 'joda' => 'application/vnd.joost.joda-archive', 'ktz' => 'application/vnd.kahootz', 'ktr' => 'application/vnd.kahootz', 'karbon' => 'application/vnd.kde.karbon', 'chrt' => 'application/vnd.kde.kchart', 'kfo' => 'application/vnd.kde.kformula', 'flw' => 'application/vnd.kde.kivio', 'kon' => 'application/vnd.kde.kontour', 'kpr' => 'application/vnd.kde.kpresenter', 'kpt' => 'application/vnd.kde.kpresenter', 'ksp' => 'application/vnd.kde.kspread', 'kwd' => 'application/vnd.kde.kword', 'kwt' => 'application/vnd.kde.kword', 'htke' => 'application/vnd.kenameaapp', 'kia' => 'application/vnd.kidspiration', 'kne' => 'application/vnd.kinar', 'knp' => 'application/vnd.kinar', 'skp' => 'application/vnd.koan', 'skd' => 'application/vnd.koan', 'skt' => 'application/vnd.koan', 'skm' => 'application/vnd.koan', 'sse' => 'application/vnd.kodak-descriptor', 'lasxml' => 'application/vnd.las.las+xml', 'lbd' => 'application/vnd.llamagraphics.life-balance.desktop', 'lbe' => 'application/vnd.llamagraphics.life-balance.exchange+xml', '123' => 'application/vnd.lotus-1-2-3', 'apr' => 'application/vnd.lotus-approach', 'pre' => 'application/vnd.lotus-freelance', 'nsf' => 'application/vnd.lotus-notes', 'org' => 'application/vnd.lotus-organizer', 'scm' => 'application/vnd.lotus-screencam', 'lwp' => 'application/vnd.lotus-wordpro', 'portpkg' => 'application/vnd.macports.portpkg', 'mcd' => 'application/vnd.mcd', 'mc1' => 'application/vnd.medcalcdata', 'cdkey' => 'application/vnd.mediastation.cdkey', 'mwf' => 'application/vnd.mfer', 'mfm' => 'application/vnd.mfmp', 'flo' => 'application/vnd.micrografx.flo', 'igx' => 'application/vnd.micrografx.igx', 'mif' => 'application/vnd.mif', 'daf' => 'application/vnd.mobius.daf', 'dis' => 'application/vnd.mobius.dis', 'mbk' => 'application/vnd.mobius.mbk', 'mqy' => 'application/vnd.mobius.mqy', 'msl' => 'application/vnd.mobius.msl', 'plc' => 'application/vnd.mobius.plc', 'txf' => 'application/vnd.mobius.txf', 'mpn' => 'application/vnd.mophun.application', 'mpc' => 'application/vnd.mophun.certificate', 'xul' => 'application/vnd.mozilla.xul+xml', 'cil' => 'application/vnd.ms-artgalry', 'cab' => 'application/vnd.ms-cab-compressed', 'xlm' => 'application/vnd.ms-excel', 'xlc' => 'application/vnd.ms-excel', 'eot' => 'application/vnd.ms-fontobject', 'chm' => 'application/vnd.ms-htmlhelp', 'ims' => 'application/vnd.ms-ims', 'lrm' => 'application/vnd.ms-lrm', 'thmx' => 'application/vnd.ms-officetheme', 'cat' => 'application/vnd.ms-pki.seccat', 'stl' => 'application/vnd.ms-pki.stl', 'mpt' => 'application/vnd.ms-project', 'wps' => 'application/vnd.ms-works', 'wks' => 'application/vnd.ms-works', 'wcm' => 'application/vnd.ms-works', 'wdb' => 'application/vnd.ms-works', 'wpl' => 'application/vnd.ms-wpl', 'mseq' => 'application/vnd.mseq', 'mus' => 'application/vnd.musician', 'msty' => 'application/vnd.muvee.style', 'taglet' => 'application/vnd.mynfc', 'nlu' => 'application/vnd.neurolanguage.nlu', 'ntf' => 'application/vnd.nitf', 'nitf' => 'application/vnd.nitf', 'nnd' => 'application/vnd.noblenet-directory', 'nns' => 'application/vnd.noblenet-sealer', 'nnw' => 'application/vnd.noblenet-web', 'ngdat' => 'application/vnd.nokia.n-gage.data', 'n-gage' => 'application/vnd.nokia.n-gage.symbian.install', 'rpst' => 'application/vnd.nokia.radio-preset', 'rpss' => 'application/vnd.nokia.radio-presets', 'edm' => 'application/vnd.novadigm.edm', 'edx' => 'application/vnd.novadigm.edx', 'ext' => 'application/vnd.novadigm.ext', 'otc' => 'application/vnd.oasis.opendocument.chart-template', 'odft' => 'application/vnd.oasis.opendocument.formula-template', 'otg' => 'application/vnd.oasis.opendocument.graphics-template', 'odi' => 'application/vnd.oasis.opendocument.image', 'oti' => 'application/vnd.oasis.opendocument.image-template', 'otp' => 'application/vnd.oasis.opendocument.presentation-template', 'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template', 'odm' => 'application/vnd.oasis.opendocument.text-master', 'ott' => 'application/vnd.oasis.opendocument.text-template', 'oth' => 'application/vnd.oasis.opendocument.text-web', 'xo' => 'application/vnd.olpc-sugar', 'dd2' => 'application/vnd.oma.dd2+xml', 'oxt' => 'application/vnd.openofficeorg.extension', 'mgp' => 'application/vnd.osgeo.mapguide.package', 'esa' => 'application/vnd.osgi.subsystem', 'pdb' => 'application/vnd.palm', 'pqa' => 'application/vnd.palm', 'oprc' => 'application/vnd.palm', 'paw' => 'application/vnd.pawaafile', 'str' => 'application/vnd.pg.format', 'ei6' => 'application/vnd.pg.osasli', 'efif' => 'application/vnd.picsel', 'wg' => 'application/vnd.pmi.widget', 'plf' => 'application/vnd.pocketlearn', 'pbd' => 'application/vnd.powerbuilder6', 'box' => 'application/vnd.previewsystems.box', 'mgz' => 'application/vnd.proteus.magazine', 'qps' => 'application/vnd.publishare-delta-tree', 'ptid' => 'application/vnd.pvi.ptid1', 'qxd' => 'application/vnd.quark.quarkxpress', 'qxt' => 'application/vnd.quark.quarkxpress', 'qwd' => 'application/vnd.quark.quarkxpress', 'qwt' => 'application/vnd.quark.quarkxpress', 'qxl' => 'application/vnd.quark.quarkxpress', 'qxb' => 'application/vnd.quark.quarkxpress', 'bed' => 'application/vnd.realvnc.bed', 'mxl' => 'application/vnd.recordare.musicxml', 'musicxml' => 'application/vnd.recordare.musicxml+xml', 'cryptonote' => 'application/vnd.rig.cryptonote', 'cod' => 'application/vnd.rim.cod', 'rm' => 'application/vnd.rn-realmedia', 'rmvb' => 'application/vnd.rn-realmedia-vbr', 'link66' => 'application/vnd.route66.link66+xml', 'st' => 'application/vnd.sailingtracker.track', 'see' => 'application/vnd.seemail', 'sema' => 'application/vnd.sema', 'semd' => 'application/vnd.semd', 'semf' => 'application/vnd.semf', 'ifm' => 'application/vnd.shana.informed.formdata', 'itp' => 'application/vnd.shana.informed.formtemplate', 'iif' => 'application/vnd.shana.informed.interchange', 'ipk' => 'application/vnd.shana.informed.package', 'twd' => 'application/vnd.simtech-mindmapper', 'twds' => 'application/vnd.simtech-mindmapper', 'mmf' => 'application/vnd.smaf', 'teacher' => 'application/vnd.smart.teacher', 'sdkm' => 'application/vnd.solent.sdkm+xml', 'sdkd' => 'application/vnd.solent.sdkm+xml', 'dxp' => 'application/vnd.spotfire.dxp', 'sfs' => 'application/vnd.spotfire.sfs', 'sdc' => 'application/vnd.stardivision.calc', 'sda' => 'application/vnd.stardivision.draw', 'sdd' => 'application/vnd.stardivision.impress', 'smf' => 'application/vnd.stardivision.math', 'sdw' => 'application/vnd.stardivision.writer', 'vor' => 'application/vnd.stardivision.writer', 'sgl' => 'application/vnd.stardivision.writer-global', 'smzip' => 'application/vnd.stepmania.package', 'sxc' => 'application/vnd.sun.xml.calc', 'stc' => 'application/vnd.sun.xml.calc.template', 'sxd' => 'application/vnd.sun.xml.draw', 'std' => 'application/vnd.sun.xml.draw.template', 'sxi' => 'application/vnd.sun.xml.impress', 'sti' => 'application/vnd.sun.xml.impress.template', 'sxm' => 'application/vnd.sun.xml.math', 'sxw' => 'application/vnd.sun.xml.writer', 'sxg' => 'application/vnd.sun.xml.writer.global', 'stw' => 'application/vnd.sun.xml.writer.template', 'sus' => 'application/vnd.sus-calendar', 'susp' => 'application/vnd.sus-calendar', 'svd' => 'application/vnd.svd', 'sis' => 'application/vnd.symbian.install', 'sisx' => 'application/vnd.symbian.install', 'xsm' => 'application/vnd.syncml+xml', 'bdm' => 'application/vnd.syncml.dm+wbxml', 'xdm' => 'application/vnd.syncml.dm+xml', 'tao' => 'application/vnd.tao.intent-module-archive', 'pcap' => 'application/vnd.tcpdump.pcap', 'cap' => 'application/vnd.tcpdump.pcap', 'dmp' => 'application/vnd.tcpdump.pcap', 'tmo' => 'application/vnd.tmobile-livetv', 'tpt' => 'application/vnd.trid.tpt', 'mxs' => 'application/vnd.triscape.mxs', 'tra' => 'application/vnd.trueapp', 'ufd' => 'application/vnd.ufdl', 'ufdl' => 'application/vnd.ufdl', 'utz' => 'application/vnd.uiq.theme', 'umj' => 'application/vnd.umajin', 'unityweb' => 'application/vnd.unity', 'uoml' => 'application/vnd.uoml+xml', 'vcx' => 'application/vnd.vcx', 'vsd' => 'application/vnd.visio', 'vst' => 'application/vnd.visio', 'vss' => 'application/vnd.visio', 'vsw' => 'application/vnd.visio', 'vis' => 'application/vnd.visionary', 'vsf' => 'application/vnd.vsf', 'wbxml' => 'application/vnd.wap.wbxml', 'wmlc' => 'application/vnd.wap.wmlc', 'wmlsc' => 'application/vnd.wap.wmlscriptc', 'wtb' => 'application/vnd.webturbo', 'nbp' => 'application/vnd.wolfram.player', 'wqd' => 'application/vnd.wqd', 'stf' => 'application/vnd.wt.stf', 'xar' => 'application/vnd.xara', 'xfdl' => 'application/vnd.xfdl', 'hvd' => 'application/vnd.yamaha.hv-dic', 'hvs' => 'application/vnd.yamaha.hv-script', 'hvp' => 'application/vnd.yamaha.hv-voice', 'osf' => 'application/vnd.yamaha.openscoreformat', 'osfpvg' => 'application/vnd.yamaha.openscoreformat.osfpvg+xml', 'saf' => 'application/vnd.yamaha.smaf-audio', 'spf' => 'application/vnd.yamaha.smaf-phrase', 'cmp' => 'application/vnd.yellowriver-custom-menu', 'zir' => 'application/vnd.zul', 'zirz' => 'application/vnd.zul', 'zaz' => 'application/vnd.zzazz.deck+xml', 'vxml' => 'application/voicexml+xml', 'wgt' => 'application/widget', 'hlp' => 'application/winhlp', 'wsdl' => 'application/wsdl+xml', 'wspolicy' => 'application/wspolicy+xml', 'abw' => 'application/x-abiword', 'ace' => 'application/x-ace-compressed', 'dmg' => 'application/x-apple-diskimage', 'aab' => 'application/x-authorware-bin', 'x32' => 'application/x-authorware-bin', 'u32' => 'application/x-authorware-bin', 'vox' => 'application/x-authorware-bin', 'aam' => 'application/x-authorware-map', 'aas' => 'application/x-authorware-seg', 'bcpio' => 'application/x-bcpio', 'torrent' => 'application/x-bittorrent', 'blb' => 'application/x-blorb', 'blorb' => 'application/x-blorb', 'bz' => 'application/x-bzip', 'bz2' => 'application/x-bzip2', 'boz' => 'application/x-bzip2', 'cbr' => 'application/x-cbr', 'cba' => 'application/x-cbr', 'cbt' => 'application/x-cbr', 'cbz' => 'application/x-cbr', 'cb7' => 'application/x-cbr', 'vcd' => 'application/x-cdlink', 'cfs' => 'application/x-cfs-compressed', 'chat' => 'application/x-chat', 'pgn' => 'application/x-chess-pgn', 'nsc' => 'application/x-conference', 'cpio' => 'application/x-cpio', 'csh' => 'application/x-csh', 'deb' => 'application/x-debian-package', 'udeb' => 'application/x-debian-package', 'dgc' => 'application/x-dgc-compressed', 'dir' => 'application/x-director', 'dcr' => 'application/x-director', 'dxr' => 'application/x-director', 'cst' => 'application/x-director', 'cct' => 'application/x-director', 'cxt' => 'application/x-director', 'w3d' => 'application/x-director', 'fgd' => 'application/x-director', 'swa' => 'application/x-director', 'wad' => 'application/x-doom', 'ncx' => 'application/x-dtbncx+xml', 'dtb' => 'application/x-dtbook+xml', 'res' => 'application/x-dtbresource+xml', 'dvi' => 'application/x-dvi', 'evy' => 'application/x-envoy', 'eva' => 'application/x-eva', 'bdf' => 'application/x-font-bdf', 'gsf' => 'application/x-font-ghostscript', 'psf' => 'application/x-font-linux-psf', 'pcf' => 'application/x-font-pcf', 'snf' => 'application/x-font-snf', 'pfa' => 'application/x-font-type1', 'pfb' => 'application/x-font-type1', 'pfm' => 'application/x-font-type1', 'afm' => 'application/x-font-type1', 'arc' => 'application/x-freearc', 'spl' => 'application/x-futuresplash', 'gca' => 'application/x-gca-compressed', 'ulx' => 'application/x-glulx', 'gnumeric' => 'application/x-gnumeric', 'gramps' => 'application/x-gramps-xml', 'gtar' => 'application/x-gtar', 'hdf' => 'application/x-hdf', 'install' => 'application/x-install-instructions', 'iso' => 'application/x-iso9660-image', 'jnlp' => 'application/x-java-jnlp-file', 'latex' => 'application/x-latex', 'lzh' => 'application/x-lzh-compressed', 'lha' => 'application/x-lzh-compressed', 'mie' => 'application/x-mie', 'prc' => 'application/x-mobipocket-ebook', 'mobi' => 'application/x-mobipocket-ebook', 'application' => 'application/x-ms-application', 'lnk' => 'application/x-ms-shortcut', 'wmd' => 'application/x-ms-wmd', 'wmz' => 'application/x-ms-wmz', 'xbap' => 'application/x-ms-xbap', 'obd' => 'application/x-msbinder', 'crd' => 'application/x-mscardfile', 'clp' => 'application/x-msclip', 'dll' => 'application/x-msdownload', 'com' => 'application/x-msdownload', 'bat' => 'application/x-msdownload', 'msi' => 'application/x-msdownload', 'mvb' => 'application/x-msmediaview', 'm13' => 'application/x-msmediaview', 'm14' => 'application/x-msmediaview', 'wmf' => 'application/x-msmetafile', 'wmz' => 'application/x-msmetafile', 'emf' => 'application/x-msmetafile', 'emz' => 'application/x-msmetafile', 'mny' => 'application/x-msmoney', 'pub' => 'application/x-mspublisher', 'scd' => 'application/x-msschedule', 'trm' => 'application/x-msterminal', 'nc' => 'application/x-netcdf', 'cdf' => 'application/x-netcdf', 'nzb' => 'application/x-nzb', 'p12' => 'application/x-pkcs12', 'pfx' => 'application/x-pkcs12', 'p7b' => 'application/x-pkcs7-certificates', 'spc' => 'application/x-pkcs7-certificates', 'p7r' => 'application/x-pkcs7-certreqresp', 'ris' => 'application/x-research-info-systems', 'sh' => 'application/x-sh', 'shar' => 'application/x-shar', 'xap' => 'application/x-silverlight-app', 'sql' => 'application/x-sql', 'sit' => 'application/x-stuffit', 'sitx' => 'application/x-stuffitx', 'sv4cpio' => 'application/x-sv4cpio', 'sv4crc' => 'application/x-sv4crc', 't3' => 'application/x-t3vm-image', 'gam' => 'application/x-tads', 'tcl' => 'application/x-tcl', 'tex' => 'application/x-tex', 'tfm' => 'application/x-tex-tfm', 'texinfo' => 'application/x-texinfo', 'texi' => 'application/x-texinfo', 'obj' => 'application/x-tgif', 'ustar' => 'application/x-ustar', 'src' => 'application/x-wais-source', 'der' => 'application/x-x509-ca-cert', 'crt' => 'application/x-x509-ca-cert', 'fig' => 'application/x-xfig', 'xlf' => 'application/x-xliff+xml', 'xpi' => 'application/x-xpinstall', 'xz' => 'application/x-xz', 'z1' => 'application/x-zmachine', 'z2' => 'application/x-zmachine', 'z3' => 'application/x-zmachine', 'z4' => 'application/x-zmachine', 'z5' => 'application/x-zmachine', 'z6' => 'application/x-zmachine', 'z7' => 'application/x-zmachine', 'z8' => 'application/x-zmachine', 'xaml' => 'application/xaml+xml', 'xdf' => 'application/xcap-diff+xml', 'xenc' => 'application/xenc+xml', 'xhtml' => 'application/xhtml+xml', 'xht' => 'application/xhtml+xml', 'xml' => 'application/xml', 'xsl' => 'application/xml', 'dtd' => 'application/xml-dtd', 'xop' => 'application/xop+xml', 'xpl' => 'application/xproc+xml', 'xslt' => 'application/xslt+xml', 'xspf' => 'application/xspf+xml', 'mxml' => 'application/xv+xml', 'xhvml' => 'application/xv+xml', 'xvml' => 'application/xv+xml', 'xvm' => 'application/xv+xml', 'yang' => 'application/yang', 'yin' => 'application/yin+xml', 'adp' => 'audio/adpcm', 'au' => 'audio/basic', 'snd' => 'audio/basic', 'kar' => 'audio/midi', 'rmi' => 'audio/midi', 'mp4a' => 'audio/mp4', 'mpga' => 'audio/mpeg', 'mp2' => 'audio/mpeg', 'mp2a' => 'audio/mpeg', 'm2a' => 'audio/mpeg', 'm3a' => 'audio/mpeg', 'spx' => 'audio/ogg', 'opus' => 'audio/ogg', 's3m' => 'audio/s3m', 'sil' => 'audio/silk', 'uva' => 'audio/vnd.dece.audio', 'uvva' => 'audio/vnd.dece.audio', 'eol' => 'audio/vnd.digital-winds', 'dra' => 'audio/vnd.dra', 'dts' => 'audio/vnd.dts', 'dtshd' => 'audio/vnd.dts.hd', 'lvp' => 'audio/vnd.lucent.voice', 'pya' => 'audio/vnd.ms-playready.media.pya', 'ecelp4800' => 'audio/vnd.nuera.ecelp4800', 'ecelp7470' => 'audio/vnd.nuera.ecelp7470', 'ecelp9600' => 'audio/vnd.nuera.ecelp9600', 'rip' => 'audio/vnd.rip', 'weba' => 'audio/webm', 'aif' => 'audio/x-aiff', 'aiff' => 'audio/x-aiff', 'aifc' => 'audio/x-aiff', 'caf' => 'audio/x-caf', 'm3u' => 'audio/x-mpegurl', 'rmp' => 'audio/x-pn-realaudio-plugin', 'xm' => 'audio/xm', 'cdx' => 'chemical/x-cdx', 'cif' => 'chemical/x-cif', 'cmdf' => 'chemical/x-cmdf', 'cml' => 'chemical/x-cml', 'csml' => 'chemical/x-csml', 'xyz' => 'chemical/x-xyz', 'ttc' => 'font/collection', 'otf' => 'font/otf', 'ttf' => 'font/ttf', 'woff' => 'font/woff', 'woff2' => 'font/woff2', 'cgm' => 'image/cgm', 'g3' => 'image/g3fax', 'ief' => 'image/ief', 'ktx' => 'image/ktx', 'btif' => 'image/prs.btif', 'sgi' => 'image/sgi', 'svg' => 'image/svg+xml', 'svgz' => 'image/svg+xml', 'uvi' => 'image/vnd.dece.graphic', 'uvvi' => 'image/vnd.dece.graphic', 'uvg' => 'image/vnd.dece.graphic', 'uvvg' => 'image/vnd.dece.graphic', 'djvu' => 'image/vnd.djvu', 'djv' => 'image/vnd.djvu', 'sub' => 'image/vnd.dvb.subtitle', 'dwg' => 'image/vnd.dwg', 'dxf' => 'image/vnd.dxf', 'fbs' => 'image/vnd.fastbidsheet', 'fpx' => 'image/vnd.fpx', 'fst' => 'image/vnd.fst', 'mmr' => 'image/vnd.fujixerox.edmics-mmr', 'rlc' => 'image/vnd.fujixerox.edmics-rlc', 'mdi' => 'image/vnd.ms-modi', 'wdp' => 'image/vnd.ms-photo', 'npx' => 'image/vnd.net-fpx', 'wbmp' => 'image/vnd.wap.wbmp', 'xif' => 'image/vnd.xiff', '3ds' => 'image/x-3ds', 'ras' => 'image/x-cmu-raster', 'cmx' => 'image/x-cmx', 'fh' => 'image/x-freehand', 'fhc' => 'image/x-freehand', 'fh4' => 'image/x-freehand', 'fh5' => 'image/x-freehand', 'fh7' => 'image/x-freehand', 'sid' => 'image/x-mrsid-image', 'pcx' => 'image/x-pcx', 'pic' => 'image/x-pict', 'pct' => 'image/x-pict', 'pnm' => 'image/x-portable-anymap', 'pbm' => 'image/x-portable-bitmap', 'pgm' => 'image/x-portable-graymap', 'ppm' => 'image/x-portable-pixmap', 'rgb' => 'image/x-rgb', 'tga' => 'image/x-tga', 'xbm' => 'image/x-xbitmap', 'xpm' => 'image/x-xpixmap', 'xwd' => 'image/x-xwindowdump', 'eml' => 'message/rfc822', 'mime' => 'message/rfc822', 'igs' => 'model/iges', 'iges' => 'model/iges', 'msh' => 'model/mesh', 'mesh' => 'model/mesh', 'silo' => 'model/mesh', 'dae' => 'model/vnd.collada+xml', 'dwf' => 'model/vnd.dwf', 'gdl' => 'model/vnd.gdl', 'gtw' => 'model/vnd.gtw', 'mts' => 'model/vnd.mts', 'vtu' => 'model/vnd.vtu', 'wrl' => 'model/vrml', 'vrml' => 'model/vrml', 'x3db' => 'model/x3d+binary', 'x3dbz' => 'model/x3d+binary', 'x3dv' => 'model/x3d+vrml', 'x3dvz' => 'model/x3d+vrml', 'x3d' => 'model/x3d+xml', 'x3dz' => 'model/x3d+xml', 'appcache' => 'text/cache-manifest', 'ifb' => 'text/calendar', 'n3' => 'text/n3', 'text' => 'text/plain', 'conf' => 'text/plain', 'def' => 'text/plain', 'list' => 'text/plain', 'log' => 'text/plain', 'in' => 'text/plain', 'dsc' => 'text/prs.lines.tag', 'sgml' => 'text/sgml', 'sgm' => 'text/sgml', 'tr' => 'text/troff', 'roff' => 'text/troff', 'man' => 'text/troff', 'me' => 'text/troff', 'ms' => 'text/troff', 'ttl' => 'text/turtle', 'uri' => 'text/uri-list', 'uris' => 'text/uri-list', 'urls' => 'text/uri-list', 'vcard' => 'text/vcard', 'curl' => 'text/vnd.curl', 'dcurl' => 'text/vnd.curl.dcurl', 'mcurl' => 'text/vnd.curl.mcurl', 'scurl' => 'text/vnd.curl.scurl', 'sub' => 'text/vnd.dvb.subtitle', 'fly' => 'text/vnd.fly', 'flx' => 'text/vnd.fmi.flexstor', '3dml' => 'text/vnd.in3d.3dml', 'spot' => 'text/vnd.in3d.spot', 'jad' => 'text/vnd.sun.j2me.app-descriptor', 'wml' => 'text/vnd.wap.wml', 'wmls' => 'text/vnd.wap.wmlscript', 'asm' => 'text/x-asm', 'cxx' => 'text/x-c', 'cpp' => 'text/x-c', 'hh' => 'text/x-c', 'dic' => 'text/x-c', 'for' => 'text/x-fortran', 'f77' => 'text/x-fortran', 'f90' => 'text/x-fortran', 'java' => 'text/x-java-source', 'nfo' => 'text/x-nfo', 'opml' => 'text/x-opml', 'pas' => 'text/x-pascal', 'etx' => 'text/x-setext', 'sfv' => 'text/x-sfv', 'uu' => 'text/x-uuencode', 'vcs' => 'text/x-vcalendar', 'vcf' => 'text/x-vcard', 'h261' => 'video/h261', 'h263' => 'video/h263', 'h264' => 'video/h264', 'jpgv' => 'video/jpeg', 'jpm' => 'video/jpm', 'jpgm' => 'video/jpm', 'mj2' => 'video/mj2', 'mjp2' => 'video/mj2', 'mp4v' => 'video/mp4', 'mpg4' => 'video/mp4', 'm1v' => 'video/mpeg', 'm2v' => 'video/mpeg', 'uvh' => 'video/vnd.dece.hd', 'uvvh' => 'video/vnd.dece.hd', 'uvm' => 'video/vnd.dece.mobile', 'uvvm' => 'video/vnd.dece.mobile', 'uvp' => 'video/vnd.dece.pd', 'uvvp' => 'video/vnd.dece.pd', 'uvs' => 'video/vnd.dece.sd', 'uvvs' => 'video/vnd.dece.sd', 'uvv' => 'video/vnd.dece.video', 'uvvv' => 'video/vnd.dece.video', 'dvb' => 'video/vnd.dvb.file', 'fvt' => 'video/vnd.fvt', 'mxu' => 'video/vnd.mpegurl', 'm4u' => 'video/vnd.mpegurl', 'pyv' => 'video/vnd.ms-playready.media.pyv', 'uvu' => 'video/vnd.uvvu.mp4', 'uvvu' => 'video/vnd.uvvu.mp4', 'viv' => 'video/vnd.vivo', 'f4v' => 'video/x-f4v', 'fli' => 'video/x-fli', 'mk3d' => 'video/x-matroska', 'mks' => 'video/x-matroska', 'mng' => 'video/x-mng', 'vob' => 'video/x-ms-vob', 'wvx' => 'video/x-ms-wvx', 'movie' => 'video/x-sgi-movie', 'smv' => 'video/x-smv', 'ice' => 'x-conference/x-cooltalk',);
                        // tmp     // we don't want to store invoices on a server really...     $mime_types = array_merge($wp_mime_types, $mime_types);
                        // tmp     // we don't want to store invoices on a server really...     $extensions = array('pdf');
                        // tmp     // we don't want to store invoices on a server really...     $allowed_mime_types = array();
                        // tmp     // we don't want to store invoices on a server really...     foreach($extensions as $ext){
                        // tmp     // we don't want to store invoices on a server really...         $key = current(preg_grep('/pdf/', array_keys($mime_types)));
                        // tmp     // we don't want to store invoices on a server really...         if($key){
                        // tmp     // we don't want to store invoices on a server really...             $allowed_mime_types[$ext] = $mime_types[$key];
                        // tmp     // we don't want to store invoices on a server really...         }
                        // tmp     // we don't want to store invoices on a server really...     }
                        // tmp     // we don't want to store invoices on a server really...     $GLOBALS['super_allowed_mime_types'] = $allowed_mime_types;
                        // tmp     // we don't want to store invoices on a server really...     add_filter( 'upload_mimes', function($mime_types){
                        // tmp     // we don't want to store invoices on a server really...         return $GLOBALS['super_allowed_mime_types'];
                        // tmp     // we don't want to store invoices on a server really...     });
                        // tmp     // we don't want to store invoices on a server really...     $body = wp_remote_retrieve_body($response);
                        // tmp     // we don't want to store invoices on a server really...     // Upload the file using wp_handle_upload()
                        // tmp     // we don't want to store invoices on a server really...     $file_name = $invoice->number.'.pdf';
                        // tmp     // we don't want to store invoices on a server really...     error_log('$file_name:' . $file_name);

                        // tmp     // we don't want to store invoices on a server really...     unset($GLOBALS['super_upload_dir']);
                        // tmp     // we don't want to store invoices on a server really...     add_filter( 'upload_dir', array( 'SUPER_Forms', 'filter_upload_dir' ));
                        // tmp     // we don't want to store invoices on a server really...     if(empty($GLOBALS['super_upload_dir'])){
                        // tmp     // we don't want to store invoices on a server really...         // upload directory is altered by filter: SUPER_Forms::filter_upload_dir()
                        // tmp     // we don't want to store invoices on a server really...         $GLOBALS['super_upload_dir'] = wp_upload_dir();
                        // tmp     // we don't want to store invoices on a server really...     }
                        // tmp     // we don't want to store invoices on a server really...     $d = $GLOBALS['super_upload_dir'];
                        // tmp     // we don't want to store invoices on a server really...     $is_secure_dir = substr($d['subdir'], 0, 3);
                        // tmp     // we don't want to store invoices on a server really...     $uploaded_file = wp_upload_bits($file_name, null, $body, $d['subdir']);
                        // tmp     // we don't want to store invoices on a server really...     error_log(json_encode($uploaded_file));
                        // tmp     // we don't want to store invoices on a server really...     if(!$uploaded_file['error']){
                        // tmp     // we don't want to store invoices on a server really...         $entry_id = $invoice->metadata->sf_entry;
                        // tmp     // we don't want to store invoices on a server really...         $stripe_connections = get_post_meta($entry_id, '_super_stripe_connections', array());
                        // tmp     // we don't want to store invoices on a server really...         error_log('PDF invoice file was uploaded successfully');
                        // tmp     // we don't want to store invoices on a server really...         error_log(json_encode($uploaded_file));
                        // tmp     // we don't want to store invoices on a server really...         // File uploaded successfully
                        // tmp     // we don't want to store invoices on a server really...         $filename = $uploaded_file['file'];
                        // tmp     // we don't want to store invoices on a server really...         error_log('$filename: '.$filename);
                        // tmp     // we don't want to store invoices on a server really...         // get allowed mime types based on this field
                        // tmp     // we don't want to store invoices on a server really...         unset($GLOBALS['super_upload_dir']);
                        // tmp     // we don't want to store invoices on a server really...         add_filter( 'upload_dir', array( 'SUPER_Forms', 'filter_upload_dir' ));
                        // tmp     // we don't want to store invoices on a server really...         if(empty($GLOBALS['super_upload_dir'])){
                        // tmp     // we don't want to store invoices on a server really...             // upload directory is altered by filter: SUPER_Forms::filter_upload_dir()
                        // tmp     // we don't want to store invoices on a server really...             $GLOBALS['super_upload_dir'] = wp_upload_dir();
                        // tmp     // we don't want to store invoices on a server really...         }
                        // tmp     // we don't want to store invoices on a server really...         $d = $GLOBALS['super_upload_dir'];
                        // tmp     // we don't want to store invoices on a server really...         $uploaded_file['id'] = $invoice->id;
                        // tmp     // we don't want to store invoices on a server really...         $uploaded_file['number'] = $invoice->number;
                        // tmp     // we don't want to store invoices on a server really...         $stripe_connections['invoice'] = $uploaded_file;
                        // tmp     // we don't want to store invoices on a server really...         update_post_meta($entry_id, '_super_stripe_connections', $stripe_connections);
                        // tmp     // we don't want to store invoices on a server really...         error_log('stripe connections: ' . json_encode($stripe_connections));
                        // tmp     // we don't want to store invoices on a server really...     }else{
                        // tmp     // we don't want to store invoices on a server really...         // File was not uploaded
                        // tmp     // we don't want to store invoices on a server really...         error_log('PDF invoice file was not uploaded');
                        // tmp     // we don't want to store invoices on a server really...     }
                        // tmp     // we don't want to store invoices on a server really... }
                        // tmp     break;
                    }
                    http_response_code(200);
                    exit;
                }
            }
        }

        public static function add_tab($tabs){
            $tabs['stripe'] = 'Stripe';
            return $tabs;
        }
        public static function add_tab_content($atts){
            $slug = SUPER_Stripe()->add_on_slug;
            $s = self::get_default_stripe_settings($atts['settings']);
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
                    echo '<div class="sfui-setting-group sfui-inline" data-f="enabled;true">';
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="conditionally" value="true"' . ($s['conditionally']==='true' ? ' checked="checked"' : '') . ' />';
                                echo '<span class="sfui-title">' . esc_html__( 'Conditionally checkout to Stripe', 'super-forms' ) . '</span>';
                            echo '</label>';
                            echo '<div class="sfui-setting sfui-inline" data-f="conditionally;true">';
                                echo '<label class="sfui-no-padding">';
                                    echo '<input type="text" name="f1" placeholder="{field}" value="' . $s['f1'] . '" />';
                                echo '</label>';
                                echo '<label class="sfui-no-padding">';
                                    echo '<select name="logic">';
                                        echo '<option'.($s['logic']==='' ?   ' selected="selected"' : '').' selected="selected" value="">---</option>';
                                        echo '<option'.($s['logic']==='==' ? ' selected="selected"' : '').' value="==">== Equal</option>';
                                        echo '<option'.($s['logic']==='!=' ? ' selected="selected"' : '').' value="!=">!= Not equal</option>';
                                        echo '<option'.($s['logic']==='??' ? ' selected="selected"' : '').' value="??">?? Contains</option>';
                                        echo '<option'.($s['logic']==='!!' ? ' selected="selected"' : '').' value="!!">!! Not contains</option>';
                                        echo '<option'.($s['logic']==='>' ?  ' selected="selected"' : '').' value=">">&gt; Greater than</option>';
                                        echo '<option'.($s['logic']==='<' ?  ' selected="selected"' : '').' value="<">&lt;  Less than</option>';
                                        echo '<option'.($s['logic']==='>=' ? ' selected="selected"' : '').' value=">=">&gt;= Greater than or equal to</option>';
                                        echo '<option'.($s['logic']==='<=' ? ' selected="selected"' : '').' value="<=">&lt;= Less than or equal</option>';
                                    echo '</select>';
                                echo '</label>';
                                echo '<label class="sfui-no-padding">';
                                    echo '<input type="text" name="f2" placeholder="'.esc_html__( 'Comparison value', 'super-forms' ).'" value="' . $s['f2'] . '" />';
                                echo '</label>';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';

                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'The mode of the Checkout Session', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Possible values', 'super-forms' ) . ': <code>payment</code> <code>subscription</code> <code>setup</code></span>';
                            echo '<input type="text" name="mode" placeholder="e.g: payment" value="' . sanitize_text_field($s['mode']) . '" />';
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
                            echo '<span class="sfui-title">' . esc_html__( 'Checkout line items', 'super-forms' ) . '</span>';
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
                                                        echo '<span class="sfui-label">' . esc_html__( 'Define a price as float value (only dot is accepted as decimal separator)', 'super-forms' ) . '</span>';
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
                                            echo '<span class="sfui-label">' . sprintf( esc_html__( 'Separate each rate with a comma. You can manage and create Tax Rates in via the Stripe %sDashboard%s.', 'super-forms' ), '<a target="_blank" href="https://dashboard.stripe.com/test/tax-rates">', '</a>' ) . '</span>';
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
                            echo '<span class="sfui-label">' . sprintf( esc_html__( 'You can create coupons easily via the %scoupon management%s page of the Stripe dashboard.', 'super-forms' ), '<a target="_blank" href="https://dashboard.stripe.com/coupons">', '</a>' ) . '</span>';
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
                                                    echo '<input type="text" name="discounts.coupon" placeholder="' . esc_html__( 'e.g: SbwGtc0x', 'super-forms' ) . '" value="' . (isset($s['discounts']['coupon']) ? sanitize_text_field($s['discounts']['coupon']) : '') . '" />';
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
                                                    echo '<input type="text" name="discounts.promotion_code" placeholder="' . esc_html__( 'e.g: SbwGtc0x', 'super-forms' ) . '" value="' . (isset($s['discounts']['promotion_code']) ? sanitize_text_field($s['discounts']['promotion_code']) : '') . '" />';
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
                                                    echo '<input type="text" name="discounts.new.name" placeholder="FALLDISCOUNT" value="' . (isset($s['discounts']['new']['name']) ? sanitize_text_field($s['discounts']['new']['name']) : '') . '" />';
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
                                                            echo '<input type="text" name="discounts.new.currency" placeholder="e.g: USD" value="' . (isset($s['shipping_options']['shipping_rate_data']['fixed_amount']['currency']) ? sanitize_text_field($s['shipping_options']['shipping_rate_data']['fixed_amount']['currency']) : '') . '" />';
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
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Customer E-mail', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'If provided, this value will be used when the Customer object is created. If not provided, customers will be asked to enter their email address. Use this parameter to prefill customer data if you already have an email on file.', 'super-forms' ) . '</span>';
                            echo '<input type="text" name="customer_email" placeholder="e.g: {email}" value="' . sanitize_text_field($s['customer_email']) . '" />';
                        echo '</label>';
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="use_logged_in_email" value="true"' . ($s['use_logged_in_email']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Override with currently logged in or newly registered user E-mail address (recommended)', 'super-forms' ) . '</span>';
                            echo '</label>';
                        echo '</div>';
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="connect_stripe_email" value="true"' . ($s['connect_stripe_email']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'If a Stripe user with this E-mail address already exists connect it to the WordPress user (recommended)', 'super-forms' ) . '</span>';
                            echo '</label>';
                        echo '</div>';
                    echo '</div>';
                    
                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<span class="sfui-title">' . esc_html__( 'Trial period in days (only works when mode is set to `subscription`)', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Integer representing the number of trial period days before the customer is charged for the first time. Has to be at least 1.', 'super-forms' ) . '</span>';
                            echo '<input type="text" name="subscription_data.trial_period_days" placeholder="e.g: 15 (leave blank for no trial period)" value="' . (isset($s['subscription_data']['trial_period_days']) ? sanitize_text_field($s['subscription_data']['trial_period_days']) : '') . '" />';
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
                            echo '<span class="sfui-title">' . esc_html__( 'Collect customer phone number', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Accepted values are', 'super-forms' ) . ': <code>false</code> <code>true</code></span>';
                            echo '<input type="text" name="phone_number_collection.enabled" value="' . ($s['phone_number_collection']['enabled']) . '" />';
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
                                                    echo '<input type="text" name="shipping_options.shipping_rate" placeholder="' . esc_html__( 'e.g: shr_XXXXXXXXXXXXXXXXXXXXXXXX', 'super-forms' ) . '" value="' . (isset($s['shipping_options']['shipping_rate']) ? sanitize_text_field($s['shipping_options']['shipping_rate']) : '') . '" />';
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
                                                echo '<input type="text" name="shipping_options.shipping_rate_data.display_name" placeholder="e.g: World wide shipping" value="' . (isset($s['shipping_options']['shipping_rate_data']['display_name']) ? sanitize_text_field($s['shipping_options']['shipping_rate_data']['display_name']) : '') . '" />';
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
                                                echo '<input type="text" name="shipping_options.shipping_rate_data.fixed_amount.amount" placeholder="e.g: 6.99" value="' . (isset($s['shipping_options']['shipping_rate_data']['fixed_amount']['amount']) ? sanitize_text_field($s['shipping_options']['shipping_rate_data']['fixed_amount']['amount']) : '') . '" />';
                                            echo '</label>';
                                            echo '<label>';
                                                echo '<span class="sfui-title">' . esc_html__( 'Shipping currency', 'super-forms' ) . '</span>';
                                                echo '<span class="sfui-label">' . sprintf( esc_html__( 'Three-letter ISO currency code. Must be a %ssupported currency%s.', 'super-forms' ), '<a target="_blank" href="https://stripe.com/docs/currencies">', '</a>' ) . '</span>';
                                                echo '<input type="text" name="shipping_options.shipping_rate_data.fixed_amount.currency" placeholder="e.g: USD" value="' . (isset($s['shipping_options']['shipping_rate_data']['fixed_amount']['currency']) ? sanitize_text_field($s['shipping_options']['shipping_rate_data']['fixed_amount']['currency']) : '') . '" />';
                                            echo '</label>';
                                            echo '<label>';
                                                echo '<span class="sfui-title">' . esc_html__( 'Shipping tax behavior', 'super-forms' ) . '</span>';
                                                echo '<span class="sfui-label">' . esc_html__( 'Specifies whether the rate is considered inclusive of taxes or exclusive of taxes.', 'super-forms' ) . ' ' . esc_html__( 'Accepted values are', 'super-forms' ) . ': <code>inclusive</code> <code>exclusive</code> <code>unspecified</code></span>'; 
                                                echo '<input type="text" name="shipping_options.shipping_rate_data.tax_behavior" placeholder="e.g: inclusive" value="' . (isset($s['shipping_options']['shipping_rate_data']['tax_behavior']) ? sanitize_text_field($s['shipping_options']['shipping_rate_data']['tax_behavior']) : '') . '" />';
                                            echo '</label>';
                                            echo '<label>';
                                                echo '<span class="sfui-title">' . esc_html__( 'Shipping tax code', 'super-forms' ) . '</span>';
                                                echo '<span class="sfui-label">' . esc_html__( 'A tax code ID. The Shipping tax code is', 'super-forms' ) . ': <code>txcd_92010001</code></span>';
                                                echo '<input type="text" name="shipping_options.shipping_rate_data.tax_code" placeholder="e.g: inclusive" value="' . (isset($s['shipping_options']['shipping_rate_data']['tax_code']) ? sanitize_text_field($s['shipping_options']['shipping_rate_data']['tax_code']) : '') . '" />';
                                            echo '</label>';
                                        echo '</div>';

                                    echo '</div>';
                                echo '</label>';
                            echo '</form>';
                        echo '</div>';
                    echo '</div>';

                    $statuses = SUPER_Settings::get_entry_statuses();
                    $entryStatusesCode = '';
                    foreach($statuses as $k => $v) {
                        if($k==='') continue;
                        if($entryStatusesCode!=='') $entryStatusesCode .= ', ';
                        $entryStatusesCode .= '<code>'.$k.'</code>';
                    }
                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Entry status after payment completed', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . sprintf( esc_html__( 'You can add custom statuses via %sSuper Forms > Settings > Backend Settings%s if needed', 'super-forms' ), '<a target="blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#backend-settings') . '">', '</a>' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Leave blank to keep the current entry status unchanged. Accepted values are:', 'super-forms' ) . ' ' . $entryStatusesCode . '</span>';
                            echo '<input type="text" name="update_entry_status" value="' . (isset($s['update_entry_status']) ? sanitize_text_field($s['update_entry_status']) : '') . '" />';
                        echo '</label>';
                    echo '</div>';
                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Entry status when subscription status changes', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Put each combination on a new line, separate the values by pipes like so: `subscription_status|entry_status`.', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Accepted values for subscription status are:', 'super-forms' ) . ' <code>active</code>, <code>paused</code> and <code>canceled</code></span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Accepted values are for entries are:', 'super-forms' ) . ' <code>delete</code> (to permanently delete the entry), <code>trash</code> (to trash the entry), ' . $entryStatusesCode . '</span>';
                            echo '<span class="sfui-label">' . sprintf( esc_html__( 'You can add custom statuses via %sSuper Forms > Settings > Backend Settings%s if needed', 'super-forms' ), '<a target="blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#backend-settings') . '">', '</a>' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Remove the line if you wish to leave the entry status unchanged.', 'super-forms' ) . '</span>';
                            echo '<textarea name="subscription.entry_status">' . (isset($s['subscription']['entry_status']) ? $s['subscription']['entry_status'] : '') . '</textarea>';
                        echo '</label>';
                    echo '</div>';

                    if(class_exists('SUPER_Frontend_Posting')){
                        $postStatusesCode = '';
                        $statuses = array(
                            'publish' => esc_html__( 'Publish (default)', 'super-forms' ),
                            'future' => esc_html__( 'Future', 'super-forms' ),
                            'draft' => esc_html__( 'Draft', 'super-forms' ),
                            'pending' => esc_html__( 'Pending', 'super-forms' ),
                            'private' => esc_html__( 'Private', 'super-forms' ),
                            'trash' => esc_html__( 'Trash', 'super-forms' ),
                            'auto-draft' => esc_html__( 'Auto-Draft', 'super-forms' )
                        );
                        foreach($statuses as $k => $v) {
                            if($k==='') continue;
                            if($postStatusesCode!=='') $postStatusesCode .= ', ';
                            $postStatusesCode .= '<code>'.$k.'</code>';
                        }
                        echo '<div class="sfui-setting sfui-vertical">';
                            echo '<label>';
                                echo '<span class="sfui-title">' . esc_html__( 'Post status after payment complete', 'super-forms' ) . '</span>';
                                echo '<span class="sfui-label">' . esc_html__( 'Only used for Front-end posting. Leave blank to keep the current post status unchanged. Accepted values are:', 'super-forms' ) . ' ' . $postStatusesCode . '</span>';
                                echo '<input type="text" name="frontend_posting.update_post_status" value="' . (isset($s['frontend_posting']['update_post_status']) ? sanitize_text_field($s['frontend_posting']['update_post_status']) : '') . '" />';
                            echo '</label>';
                        echo '</div>';
                        echo '<div class="sfui-setting sfui-vertical">';
                            echo '<label>';
                                echo '<span class="sfui-title">' . esc_html__( 'Post status when subscription status changes', 'super-forms' ) . '</span>';
                                echo '<span class="sfui-label">' . esc_html__( 'Only used for Front-end posting.', 'super-forms' ) . '</span>';
                                echo '<span class="sfui-label">' . esc_html__( 'Put each combination on a new line, separate the values by pipes like so: `subscription_status|post_status`.', 'super-forms' ) . '</span>';
                                echo '<span class="sfui-label">' . esc_html__( 'Accepted values for subscription status are:', 'super-forms' ) . ' <code>active</code>, <code>paused</code> and <code>canceled</code></span>';
                                echo '<span class="sfui-label">' . esc_html__( 'Accepted values for posts are:', 'super-forms' ) . ' <code>delete</code> (to permanently delete the post), <code>trash</code> (to trash the post), ' . $postStatusesCode . '</span>';
                                echo '<span class="sfui-label">' . esc_html__( 'Remove the line if you wish to leave the post status unchanged.', 'super-forms' ) . '</span>';
                                echo '<textarea name="subscription.post_status">' . (isset($s['subscription']['post_status']) ? $s['subscription']['post_status'] : '') . '</textarea>';
                            echo '</label>';
                        echo '</div>';
                    }

                    // Register & Login features
                    if(class_exists('SUPER_Register_Login')){
                        global $wp_roles;
                        $all_roles = $wp_roles->roles;
                        $editable_roles = apply_filters( 'editable_roles', $all_roles );
                        $rolesCode = '';
                        foreach( $editable_roles as $k => $v ) {
                            if($rolesCode!=='') $rolesCode .= ', ';
                            $rolesCode .= '<code>'.$k.'</code>';
                        }
                        $userLoginStatusesCode = '';
                        $statuses = array(
                            'active' => esc_html__( 'Active (default)', 'super-forms' ),
                            'pending' => esc_html__( 'Pending', 'super-forms' ),
                            'payment_required' => esc_html__( 'Payment required', 'super-forms' ),
                            'blocked' => esc_html__( 'Blocked', 'super-forms' )
                        );
                        foreach($statuses as $k => $v) {
                            if($k==='') continue;
                            if($userLoginStatusesCode!=='') $userLoginStatusesCode .= ', ';
                            $userLoginStatusesCode .= '<code>'.$k.'</code>';
                        }
                        echo '<div class="sfui-setting sfui-vertical">';
                            echo '<label>';
                                echo '<span class="sfui-title">' . esc_html__( 'User login status after payment complete', 'super-forms' ) . '</span>';
                                echo '<span class="sfui-label">' . esc_html__( 'Only used when registering a new user. You would normally want this to be set to `active` so that the user is able to login. Leave blank to keep the current login status unchanged. Accepted values are:', 'super-forms' ) . ' ' . $userLoginStatusesCode . '</span>';
                                echo '<input type="text" name="register_login.update_login_status" value="' . (isset($s['register_login']['update_login_status']) ? sanitize_text_field($s['register_login']['update_login_status']) : '') . '" />';
                            echo '</label>';
                            echo '<label>';
                                echo '<span class="sfui-title">' . esc_html__( 'User login status when subscription status changes', 'super-forms' ) . '</span>';
                                echo '<span class="sfui-label">' . esc_html__( 'Put each combination on a new line, separate the values by pipes like so `subscription_status|user_login_status`.', 'super-forms' ) . '</span>';
                                echo '<span class="sfui-label">' . esc_html__( 'Accepted values for subscription status are:', 'super-forms' ) . ' <code>active</code>, <code>paused</code> and <code>canceled</code></span>';
                                echo '<span class="sfui-label">' . esc_html__( 'Accepted values for login status are:', 'super-forms' ) . ' ' . $userLoginStatusesCode . '</span>';
                                echo '<span class="sfui-label">' . esc_html__( 'Remove the line if you wish to leave the login status unchanged.', 'super-forms' ) . '</span>';
                                echo '<textarea name="subscription.login_status">' . (isset($s['subscription']['login_status']) ? $s['subscription']['login_status'] : '') . '</textarea>';
                            echo '</label>';
                            echo '<label>';
                                echo '<span class="sfui-title">' . esc_html__( 'Change user role after payment complete', 'super-forms' ) . '</span>';
                                echo '<span class="sfui-label">' . esc_html__( 'Leave blank to keep the current user role unchanged. Accepted values are:', 'super-forms' ) . ' ' . $rolesCode . '</span>';
                                echo '<input type="text" name="register_login.update_user_role" value="' . (isset($s['register_login']['update_user_role']) ? sanitize_text_field($s['register_login']['update_user_role']) : '') . '" />';
                            echo '</label>';
                            echo '<label>';
                                echo '<span class="sfui-title">' . esc_html__( 'Change user role when subscription status changes', 'super-forms' ) . '</span>';
                                echo '<span class="sfui-label">' . esc_html__( 'Put each combination on a new line, separate the values by pipes like so: `subscription_status|user_role`.', 'super-forms' ) . '</span>';
                                echo '<span class="sfui-label">' . esc_html__( 'Accepted values for subscription status are:', 'super-forms' ) . ' <code>active</code>, <code>paused</code> and <code>canceled</code></span>';
                                echo '<span class="sfui-label">' . esc_html__( 'Accepted values for user roles are:', 'super-forms' ) . ' ' . $rolesCode . '</span>';
                                echo '<span class="sfui-label">' . esc_html__( 'Remove the line if you wish to leave the user role unchanged.', 'super-forms' ) . '</span>';
                                echo '<textarea name="subscription.user_role">' . (isset($s['subscription']['user_role']) ? $s['subscription']['user_role'] : '') . '</textarea>';
                            echo '</label>';
                        echo '</div>';
                    }

                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<div class="sfui-notice sfui-desc">';
                            echo '<strong>' . esc_html__('Note', 'super-forms') . ':</strong> ' . sprintf( esc_html__( 'This option only affects Checkout Sessions with `payment` as mode. Set this to `false` when you are creating invoices outside of Stripe. Set this to `true` if you want Stripe to create invoices automatically for one-time payments. To send invoice summary emails to your customer, you must make sure you enable the %1$sEmail customers about successful payments%2$s in your Stripe Dashboard. You can also prevent Stripe from sending these emails by %1$sdisabling the setting%2$s in your Stripe Dashboard. If a delayed payment method is used, the invoice will be send after successful payment.', 'super-forms' ), '<a href="https://dashboard.stripe.com/settings/emails" target="_blank">', '</a>');
                        echo '</div>';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Create invoice', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( '', 'super-forms' ) . ' ' . esc_html__( 'Accepted values are', 'super-forms' ) . ': <code>true</code> <code>false</code></span>';
                            echo '<input type="text" name="invoice_creation" value="' . sanitize_text_field($s['invoice_creation']) . '" />';
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

                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<span class="sfui-title">' . esc_html__( 'The subscription’s description, meant to be displayable to the customer.', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Use this field to optionally store an explanation of the subscription for rendering in Stripe hosted surfaces.', 'super-forms' ) . '</span>';
                            echo '<input type="text" name="subscription_data.description" placeholder="e.g: Website updates and maintenance" value="' . (isset($s['subscription_data']['description']) ? sanitize_text_field($s['subscription_data']['description']) : '') . '" />';
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
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<span class="sfui-title">' . esc_html__( 'The IETF language tag of the locale Checkout is displayed in. If blank or auto, the browser’s locale is used.', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Possible values', 'super-forms' ) . ': <code>auto</code> <code>bg</code> <code>cs</code> <code>da</code> <code>de</code> <code>el</code> <code>en</code> <code>en-GB</code> <code>es</code> <code>es-419</code> <code>et</code> <code>fi</code> <code>fil</code> <code>fr</code> <code>fr-CA</code> <code>hr</code> <code>hu</code> <code>id</code> <code>it</code> <code>ja</code> <code>ko</code> <code>lt</code> <code>lv</code> <code>ms</code> <code>mt</code> <code>nb</code> <code>nl</code> <code>pl</code> <code>pt</code> <code>pt-BR</code> <code>ro</code> <code>ru</code> <code>sk</code> <code>sl</code> <code>sv</code> <code>th</code> <code>tr</code> <code>vi</code> <code>zh</code> <code>zh-HK</code> <code>zh-TW</code></span>';
                            echo '<input type="text" name="locale" placeholder="e.g: en" value="' . sanitize_text_field($s['locale']) . '" />';
                        echo '</label>';
                    echo '</div>';

                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Client reference ID (for developers only)', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'A unique string to reference the Checkout Session. This can be a customer ID, a cart ID, or similar, and can be used to reconcile the session with your internal systems.', 'super-forms' ) . '</span>';
                            echo '<input type="text" name="client_reference_id" placeholder="" value="' . sanitize_text_field($s['client_reference_id']) . '" />';
                        echo '</label>';
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
        public static function get_default_stripe_settings($settings=array(), $s=array()) {
            if(empty($s['enabled'])) $s['enabled'] = 'false';
            if(empty($s['conditionally'])) $s['conditionally'] = array(
                'conditionally' => 'false', 
                'f1' => '', 
                'f2' => '', 
                'logic' => ''
            );
            if(empty($s['mode'])) $s['mode'] = 'payment'; // The mode of the Checkout Session. Required when using prices or setup mode. Pass subscription if the Checkout Session includes at least one recurring item.
            if(empty($s['submit_type'])) $s['submit_type'] = 'auto'; // Describes the type of transaction being performed by Checkout in order to customize relevant text on the page, such as the submit button. submit_type can only be specified on Checkout Sessions in payment mode, but not Checkout Sessions in subscription or setup mode.
            if(empty($s['cancel_url'])) $s['cancel_url'] = ''; // The URL the customer will be directed to if they decide to cancel payment and return to your website.
            if(empty($s['success_url'])) $s['success_url'] = ''; // The URL to which Stripe should send customers when payment or setup is complete. If you’d like to use information from the successful Checkout Session on your page, read the guide on customizing your success page.
            if(empty($s['customer_email'])) $s['customer_email'] = ''; // If provided, this value will be used when the Customer object is created. If not provided, customers will be asked to enter their email address. Use this parameter to prefill customer data if you already have an email on file. To access information about the customer once a session is complete, use the customer field.
            if(empty($s['use_logged_in_email'])) $s['use_logged_in_email'] = 'true'; // When enabled, use the currently logged in user email address
            if(empty($s['connect_stripe_email'])) $s['connect_stripe_email'] = 'true'; // Connect with existing Stripe user based on E-mail address if one exists
            if(empty($s['client_reference_id'])) $s['client_reference_id'] = ''; // A unique string to reference the Checkout Session. This can be a customer ID, a cart ID, or similar, and can be used to reconcile the session with your internal systems.
            if(empty($s['metadata'])) $s['metadata'] = '';
            if(empty($s['payment_method_types'])) $s['payment_method_types'] = 'card';
            if(empty($s['invoice_creation'])) $s['invoice_creation'] = 'true';
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
            if(empty($s['update_entry_status'])) $s['update_entry_status'] = '';
            if(empty($s['subscription'])) {
                $s['subscription'] = array(
                    'entry_status' => "active|active\npaused|pending\ncanceled|trash",
                    'post_status' => "active|publish\npaused|pending\ncanceled|trash",
                    'login_status' => "active|active\npaused|blocked\ncanceled|blocked",
                    'user_role' => "active|subscriber\npaused|customer\ncanceled|customer"
                );
            }
            if(empty($s['frontend_posting'])) $s['frontend_posting'] = array(
                'update_post_status' => ''
            );
            if(empty($s['register_login'])) $s['register_login'] = array(
                'update_login_status' => 'active',
                'update_user_role' => ''
            );
            if(empty($s['line_items'])) $s['line_items'] = array(
                array(
                    'type' => 'price', // (not a Stripe key) Type, either `price` or `price_data`
                    'price' => '', // The ID of the Price or Plan object. One of price or price_data is required.
                    'quantity' => 1, // The quantity of the line item being purchased. Quantity should not be defined when recurring.usage_type=metered.
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
                    // 'tax_rates' => array( // The tax rates which apply to this line item.
                    //     '{{TAX_RATE_ID}}',
                    // )
                    'custom_tax_rate' => '',
                    'tax_rates' => '', // comma separated list
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
            if(isset($settings) && isset($settings['_stripe'])){
                $s = array_merge($s, $settings['_stripe']);
            }
            return $s;
        }


        /**
         * Create Stripe Payment Intent
         *
         *  @since      1.0.0
         */
        public static function redirect_to_stripe_checkout($x){
            extract( shortcode_atts( array(
                'sfsi'=>array(),
                'form_id'=>0,
                'uniqueSubmissionId'=>'',
                'post'=>array(), 
                'data'=>array(), 
                'settings'=>array(), 
                'entry_id'=>0, 
                'attachments'=>array()
            ), $x));
            //error_log('redirect_to_stripe_checkout()');
            //error_log(json_encode($x));
            //error_log('Entry ID: '.$sfsi['entry_id']);
            //error_log('User ID: '.$sfsi['user_id']);
            $domain = home_url(); // e.g: 'http://domain.com';
            $home_url = trailingslashit($domain);
            if(empty($settings['_stripe'])) return true;
            $s = $settings['_stripe'];
            // Skip if Stripe checkout is not enabled
            if($s['enabled']!=='true') return true;
            // If conditional check is enabled
            $checkout = true;
            if($s['conditionally']==='true' && $s['logic']!==''){
                $checkout = false;
                $s['f1'] = SUPER_Common::email_tags($s['f1'], $data, $settings);
                $s['f2'] = SUPER_Common::email_tags($s['f2'], $data, $settings);
                if($s['logic']==='==' && ($s['f1']===$s['f2'])) $checkout = true;
                if($s['logic']==='!=' && ($s['f1']!==$s['f2'])) $checkout = true;
                if($s['logic']==='??' && (strpos($s['f1'], $s['f2'])!==false)) $checkout = true; // Contains
                if($s['logic']==='!!' && (strpos($s['f1'], $s['f2'])===false)) $checkout = true; // Not cointains
                if($s['logic']==='>' && ($s['f1']>$s['f2'])) $checkout = true;
                if($s['logic']==='<' && ($s['f1']<$s['f2'])) $checkout = true;
                if($s['logic']==='>=' && ($s['f1']>=$s['f2'])) $checkout = true;
                if($s['logic']==='<=' && ($s['f1']<=$s['f2'])) $checkout = true;
            }
            if($checkout===false) return true;
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
            $cancel_url = $home_url . 'sfssid/cancel/{CHECKOUT_SESSION_ID}';
            $success_url = $home_url . 'sfssid/success/{CHECKOUT_SESSION_ID}';
            $mode = SUPER_Common::email_tags( $s['mode'], $data, $settings );
            $customer_email = (isset($s['customer_email']) ? SUPER_Common::email_tags( $s['customer_email'], $data, $settings ) : '');
            $customer = '';
            if($s['use_logged_in_email']==='true'){
                // Check if user is logged in, or a newly user was registerd
                $user_id = get_current_user_id();
                error_log('user_id: '.$user_id);
                error_log('Entry ID stripe redirect: '.$sfsi['entry_id']);
                error_log('User ID stripe redirect: '.$sfsi['user_id']);
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
                if(!empty($email)) $customer_email = $email;
                try {
                    $create_new_customer = true;
                    // Check if user is already connected to a stripe user
                    $super_stripe_cus = get_user_meta( $user_id, 'super_stripe_cus', true );
                    if(!empty($super_stripe_cus)){
                        error_log('WP user is already connected to a stripe user');
                        $customer = \Stripe\Customer::retrieve($super_stripe_cus);
                    }
                    if(empty($customer)){
                        error_log('WP user is not yet connected to stripe user, or was not found');
                        // Try to lookup by E-mail?
                        if($s['connect_stripe_email']==='true'){
                            error_log('lookup by E-mail?');
                            $customers = \Stripe\Customer::all(['email'=>$customer_email]);
                            if(!empty($customers) && !empty($customers->data)){
                                $customer = $customers->data[0];
                            }
                        }
                    }
                    if(!empty($customer)){
                        // Check if customer was deleted
                        if(!empty($customer['deleted']) && $customer['deleted']==true){
                            // Customer was deleted, we should create a new
                            error_log('This Stripe user was deleted?');
                        }else{
                            // The customer exists, make sure we do not create a new customer
                            error_log('This Stripe user still exists..., use it');
                            $create_new_customer = false; 
                            $customer = $customer->id;
                            error_log('Stripe customer exists, and is already connected to the this WP user: '. $customer . ' - ' . $user_id);
                        }
                    }
                    if($create_new_customer){
                        // Customer doesn't exists, create a new customer
                        $customer = \Stripe\Customer::create(['email' => $email]);
                        error_log('Attach stripe customer to wp user: '. $customer->id . ' - ' . $user_id);
                        update_user_meta($user_id, 'super_stripe_cus', $customer->id);
                        $customer = $customer->id;
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
                            // Customer doesn't exists, create a new customer
                            $customer = \Stripe\Customer::create(['email' => $email]);
                            update_user_meta($user_id, 'super_stripe_cus', $customer->id);
                            $customer = $customer->id;
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

                //if(isset($metadata['frontend_user_id'])){
                //    if(absint($metadata['frontend_user_id'])!==0){
                //        $user_id = $metadata['frontend_user_id'];
                //    }
                //}
                //$super_stripe_cus = get_user_meta( $user_id, 'super_stripe_cus', true );
            }
            //var_dump($customer);
            $description = (isset($s['subscription_data']['description']) ? SUPER_Common::email_tags( $s['subscription_data']['description'], $data, $settings ) : '');
            $trial_period_days = (isset($s['subscription_data']['trial_period_days']) ? SUPER_Common::email_tags( $s['subscription_data']['trial_period_days'], $data, $settings ) : '');
            $payment_methods = (isset($s['payment_method_types']) ? SUPER_Common::email_tags( $s['payment_method_types'], $data, $settings ) : '');
            $payment_methods = explode(',', str_replace(' ', '', $payment_methods));
            $metadata = array(
                'sf_id' => $form_id,
                'sf_entry' => $entry_id,
                'sf_user' => (isset($sfsi['user_id']) ? $sfsi['user_id'] : 0),
                'sf_post' => (isset($sfsi['created_post']) ? $sfsi['created_post'] : 0),
                'sfsi_id' => $uniqueSubmissionId
            );
            error_log('custom metadata for stripe session and invoice: ' . json_encode($metadata));
            $home_cancel_url = (isset($s['cancel_url']) ? SUPER_Common::email_tags( $s['cancel_url'], $data, $settings ) : '');
            $home_success_url = (isset($s['success_url']) ? SUPER_Common::email_tags( $s['success_url'], $data, $settings ) : '');
            if($home_cancel_url==='') $home_cancel_url = $_SERVER['HTTP_REFERER'];
            if($home_success_url==='') $home_success_url = $_SERVER['HTTP_REFERER'];

            $submissionInfo = get_option( '_sfsi_' . $uniqueSubmissionId, array() );
            error_log('submissionInfo: '.json_encode($submissionInfo));
            $submissionInfo['entry_id'] = $entry_id;
            $submissionInfo['stripe_home_cancel_url'] = $home_cancel_url;
            $submissionInfo['stripe_home_success_url'] = $home_success_url;
            SUPER_Common::setClientData( array( 'name'=>'stripe_home_cancel_url_'.$form_id, 'value'=>$home_cancel_url ) );
            SUPER_Common::setClientData( array( 'name'=>'stripe_home_success_url_'.$form_id, 'value'=>$home_success_url ) );
            update_option('_sfsi_' . $uniqueSubmissionId, $submissionInfo );
            // Shipping options
            $shipping_options = array();
            if($s['shipping_options']['type']==='id'){
                $shipping_options['shipping_rate'] = (isset($s['shipping_options']['shipping_rate']) ? $s['shipping_options']['shipping_rate'] : '');
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
            //error_log('metadata: ' . json_encode($metadata));

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
            if(!empty($global_settings['stripe_mode']) ) {
                $global_settings['stripe_mode'] = 'sandbox';
            }else{
                $global_settings['stripe_mode'] = 'live';
            }
            // Get webhook ID and secret
            $webhookId = (isset($global_settings['stripe_' . $global_settings['stripe_mode'] . '_webhook_id']) ? $global_settings['stripe_' . $global_settings['stripe_mode'] . '_webhook_id'] : '');
            $webhookSecret = (isset($global_settings['stripe_' . $global_settings['stripe_mode'] . '_webhook_secret']) ? $global_settings['stripe_' . $global_settings['stripe_mode'] . '_webhook_secret'] : '');
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
                error_log("exceptionHandler20()");
                self::exceptionHandler($e, $metadata);
                die();
            }
            // Check if this webhook has a correct endpoint URL and Events defined
            $webhook = \Stripe\WebhookEndpoint::retrieve($webhookId, []);
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
            if( !in_array('checkout.session.expired', $webhook['enabled_events']) ) {
                $eventMissing = true;
            }
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
                //error_log('shipping_options');
                //error_log(json_encode($shipping_options));
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

                    'line_items' => $line_items,
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
                    'locale' => (isset($s['locale']) ? $s['locale'] : ''),

                    'automatic_tax' => $s['automatic_tax'],
                    'tax_id_collection' => $s['tax_id_collection'],

                    'subscription_data' => array(
                        'description' => $description,
                        'trial_period_days' => $trial_period_days,
                        'metadata' => $metadata
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
                    'client_reference_id' => (isset($s['client_reference_id']) ? $s['client_reference_id'] : '')

                    // We don't use this, since it requires consent from the user
                    // // Expires after
                    // 'expires_at' => time() + (3600 * 0.5), // Configured to expire after 30 min. 
                    // // Allow recovery 
                    // 'consent_collection' => array(
                    //     'promotions' => 'auto', // Promotional consent is required to send recovery emails
                    // ),
                    // 'after_expiration' => array(
                    //     'recovery' => array(
                    //         'enabled' => true,
                    //         'allow_promotion_codes' => true,
                    //     ),
                    // )
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
                    $stripeData['submit_type'] = (!empty($s['submit_type']) ? $s['submit_type'] : 'auto');

                    // Create invoice after the payment completes, array('enabled' => true), 
                    $invoice_creation = (!empty(trim($s['invoice_creation'])) ? SUPER_Common::email_tags( $s['invoice_creation'], $data, $settings ) : 'true');
                    // If mode is not `payment` disable invoice creation because since
                    // You can only enable invoice creation when `mode` is set to `payment`. 
                    // Invoices are created automatically when `mode` is set to `subscription`, and are unsupported when set to `setup`. 
                    // To learn more visit https://stripe.com/docs/payments/checkout/post-payment-invoices.
                    if($invoice_creation==='true'){
                        $invoice_creation = 'true';
                    }else{
                        $invoice_creation = 'false';
                    }
                    // Create invoice after the payment completes, array('enabled' => true), 
                    $stripeData['invoice_creation'] = array(
                        'enabled' => $invoice_creation,
                        'invoice_data' => array(
                            'metadata' => $metadata
                        )
                    );
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

                // You may only specify one of these parameters: customer, customer_email.
                // You may only specify one of these parameters: customer, customer_creation.
                if($stripeData['customer']!==''){
                    unset($stripeData['customer_email']);
                    unset($stripeData['customer_creation']);
                }
                // You may only specify one of these parameters: submit_type, subscription_data.
                error_log(json_encode($stripeData));
                if(isset($stripeData['submit_type']) && $mode!=='subscription'){
                    unset($stripeData['subscription_data']);
                }
                //if($stripeData['submit_type']!=='payment'){
                //    unset($stripeData['subscription_data']);
                //}
                
                $stripeData = array_remove_empty($stripeData);
                $stripeData = apply_filters( 'super_stripe_checkout_session_create_data_filter', $stripeData );
                // Append stripe data to submission info
                $submissionInfo = get_option( '_sfsi_' . $uniqueSubmissionId, array() );
                $submissionInfo['stripeData'] = $stripeData;
                update_option('_sfsi_' . $uniqueSubmissionId, $submissionInfo );
                // Create the checkout session via Stripe API
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
            // Redirect to Stripe checkout page
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
                //error_log('delete entry #'.$contact_entry_id);
                wp_delete_post($contact_entry_id, true); // force delete, we no longer want it in our system
            }
            // Delete post after failed payment (only used for Front-end Posting feature)
            $frontend_post_id = (isset($metadata['_super_stripe_frontend_post_id']) ? absint($metadata['_super_stripe_frontend_post_id']) : 0 );
            if( !empty($frontend_post_id) ) {
                //error_log('delete post #'.$frontend_post_id);
                wp_delete_post($frontend_post_id, true); // force delete, we no longer want it in our system
            }
            // Delete user after failed payment (only used for Register & Login feature)
            $frontend_user_id = (isset($metadata['_super_stripe_frontend_user_id']) ? absint($metadata['_super_stripe_frontend_user_id']) : 0 );
            if( !empty($frontend_user_id) ) {
                require_once( ABSPATH . 'wp-admin/includes/user.php' );
                //error_log('delete user #'.$frontend_user_id);
                wp_delete_user($frontend_user_id);
            }
            // Delete any E-mail reminders based on this form ID as it's parent
            $email_reminders = (isset($metadata['super_forms_email_reminders']) ? $metadata['super_forms_email_reminders'] : array() );
            if (is_array($email_reminders) && count($email_reminders) > 0) {
                // Delete all the Children of the Parent Page
                foreach($email_reminders as $reminder){
                    //error_log('delete reminder #'.$reminder);
                    wp_delete_post($reminder, true);  // force delete, we no longer want it in our system
                }
            }
        }

        // When charge succeeded
        public function charge_succeeded($paymentIntent){
            //error_log("charge_succeeded()");
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
                                    throw new Exception($result->get_error_message());
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

        // tmp public static function super_enqueue_styles($styles){
        // tmp     $styles['super-stripe-dashboard'] = array(
        // tmp         'src'     => plugin_dir_url( __FILE__ ) . 'stripe-dashboard.css',
        // tmp         'deps'    => array(),
        // tmp         'version' => SUPER_VERSION,
        // tmp         'media'   => 'all',
        // tmp         'screen'  => array(
        // tmp             'super-forms_page_super_stripe_dashboard'
        // tmp         ),
        // tmp         'method'  => 'enqueue',
        // tmp     );
        // tmp     return $styles;
        // tmp }
        // tmp public static function super_enqueue_scripts($scripts){
        // tmp     $global_settings = SUPER_Common::get_global_settings();
        // tmp     $scripts['super-stripe-dashboard'] = array(
        // tmp         'src'     => plugin_dir_url( __FILE__ ) . 'stripe-dashboard.js',
        // tmp         'deps'    => array(),
        // tmp         'version' => SUPER_VERSION,
        // tmp         'footer'  => true,
        // tmp         'screen'  => array( 
        // tmp             'super-forms_page_super_stripe_dashboard'
        // tmp         ),
        // tmp         'method'  => 'register', // Register because we need to localize it
        // tmp         'localize'=> array(
        // tmp             'sandbox' => ( !empty($global_settings['stripe_mode']) ? 'true' : 'false' ),
        // tmp             'dashboardUrl' => 'https://dashboard.stripe.com' . ( !empty($global_settings['stripe_mode']) ? '/test' : '' ),
        // tmp             'viewOnlineInvoice' => esc_html__( 'View online invoice', 'super-forms' ),
        // tmp             'refundReasons' => array(
        // tmp                 'duplicate' => esc_html__( 'Add more details about this refund', 'super-forms' ),
        // tmp                 'fraudulent' => esc_html__( 'Why is this payment fraudulent?', 'super-forms' ),
        // tmp                 'requested_by_customer' => esc_html__( 'Add more details about this refund', 'super-forms' ),
        // tmp                 'other' => esc_html__( 'Add a reason for this refund', 'super-forms' ),
        // tmp                 'other_note' => esc_html__( 'A note is required when a provided reason isn’t selected', 'super-forms' )
        // tmp             ),
        // tmp             'declineCodes' => array(
        // tmp                 'authentication_required' => array(
        // tmp                     'desc' => esc_html__( 'The card was declined as the transaction requires authentication.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should try again and authenticate their card when prompted during the transaction.', 'super-forms' )
        // tmp                 ),
        // tmp                 'approve_with_id' => array(
        // tmp                     'desc' => esc_html__( 'The payment cannot be authorized.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The payment should be attempted again. If it still cannot be processed, the customer needs to contact their card issuer.', 'super-forms' )
        // tmp                 ),
        // tmp                 'call_issuer' => array(
        // tmp                     'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'card_not_supported' => array(
        // tmp                     'desc' => esc_html__( 'The card does not support this type of purchase.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer needs to contact their card issuer to make sure their card can be used to make this type of purchase.', 'super-forms' )
        // tmp                 ),
        // tmp                 'card_velocity_exceeded' => array(
        // tmp                     'desc' => esc_html__( 'The customer has exceeded the balance or credit limit available on their card.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'currency_not_supported' => array(
        // tmp                     'desc' => esc_html__( 'The card does not support the specified currency.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer needs to check with the issuer whether the card can be used for the type of currency specified.', 'super-forms' )
        // tmp                 ),
        // tmp                 'do_not_honor' => array(
        // tmp                     'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'do_not_try_again' => array(
        // tmp                     'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'duplicate_transaction' => array(
        // tmp                     'desc' => esc_html__( 'A transaction with identical amount and credit card information was submitted very recently.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'Check to see if a recent payment already exists.', 'super-forms' )
        // tmp                 ),
        // tmp                 'expired_card' => array(
        // tmp                     'desc' => esc_html__( 'The card has expired.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should use another card.', 'super-forms' )
        // tmp                 ),
        // tmp                 'fraudulent' => array(
        // tmp                     'desc' => esc_html__( 'The payment has been declined as Stripe suspects it is fraudulent.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'Do not report more detailed information to your customer.  Instead, present as you would the ', 'super-forms' )
        // tmp                 ),
        // tmp                 'generic_decline' => array(
        // tmp                     'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'incorrect_number' => array(
        // tmp                     'desc' => esc_html__( 'The card number is incorrect.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should try again using the correct card number.', 'super-forms' )
        // tmp                 ),
        // tmp                 'incorrect_cvc' => array(
        // tmp                     'desc' => esc_html__( 'The CVC number is incorrect.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should try again using the correct CVC.', 'super-forms' )
        // tmp                 ),
        // tmp                 'incorrect_pin' => array(
        // tmp                     'desc' => esc_html__( 'The PIN entered is incorrect. This decline code only applies to payments made with a card reader. ', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should try again using the correct PIN.', 'super-forms' )
        // tmp                 ),
        // tmp                 'incorrect_zip' => array(
        // tmp                     'desc' => esc_html__( 'The ZIP/postal code is incorrect.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should try again using the correct billing ZIP/postal code.', 'super-forms' )
        // tmp                 ),
        // tmp                 'insufficient_funds' => array(
        // tmp                     'desc' => esc_html__( 'The card has insufficient funds to complete the purchase.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should use an alternative payment method.', 'super-forms' )
        // tmp                 ),
        // tmp                 'invalid_account' => array(
        // tmp                     'desc' => esc_html__( 'The card or account the card is connected to, is invalid.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer needs to contact their card issuer to check that the card is working correctly.', 'super-forms' )
        // tmp                 ),
        // tmp                 'invalid_amount' => array(
        // tmp                     'desc' => esc_html__( 'The payment amount is invalid or exceeds the amount that is allowed.', 'super-forms' ),
        // tmp                      'steps' => esc_html__( 'If the amount appears to be correct, the customer needs to check with their card issuer that they can make purchases of that amount.', 'super-forms' )
        // tmp                 ),
        // tmp                 'invalid_cvc' => array(
        // tmp                     'desc' => esc_html__( 'The CVC number is incorrect.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should try again using the correct CVC.', 'super-forms' )
        // tmp                 ),
        // tmp                 'invalid_expiry_year' => array(
        // tmp                     'desc' => esc_html__( 'The expiration year invalid.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should try again using the correct expiration date.', 'super-forms' )
        // tmp                 ),
        // tmp                 'invalid_number' => array(
        // tmp                     'desc' => esc_html__( 'The card number is incorrect.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should try again using the correct card number.', 'super-forms' )
        // tmp                 ),
        // tmp                 'invalid_pin' => array(
        // tmp                     'desc' => esc_html__( 'The PIN entered is incorrect. This decline code only applies to payments made with a card reader.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should try again using the correct PIN.', 'super-forms' )
        // tmp                 ),
        // tmp                 'issuer_not_available' => array(
        // tmp                     'desc' => esc_html__( 'The card issuer could not be reached so the payment could not be authorized.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The payment should be attempted again. If it still cannot be processed, the customer needs to contact their card issuer.', 'super-forms' )
        // tmp                 ),
        // tmp                 'lost_card' => array(
        // tmp                     'desc' => esc_html__( 'The payment has been declined because the card is reported lost.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The specific reason for the decline should not be reported to the customer. Instead, it needs to be presented as a generic decline.', 'super-forms' )
        // tmp                 ),
        // tmp                 'merchant_blacklist' => array(
        // tmp                     'desc' => esc_html__( 'The payment has been declined because it matches a value on the Stripe user’s block list.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'Do not report more detailed information to your customer. Instead, present as you would the ', 'super-forms' )
        // tmp                 ),
        // tmp                 'new_account_information_available' => array(
        // tmp                     'desc' => esc_html__( 'The card or account the card is connected to, is invalid.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'no_action_taken' => array(
        // tmp                     'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'not_permitted' => array(
        // tmp                     'desc' => esc_html__( 'The payment is not permitted.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'offline_pin_required' => array(
        // tmp                     'desc' => esc_html__( 'The card has been declined as it requires a PIN.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should try again by inserting their card and entering a PIN.', 'super-forms' )
        // tmp                 ),
        // tmp                 'online_or_offline_pin_required' => array(
        // tmp                     'desc' => esc_html__( 'The card has been declined as it requires a PIN.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'If the card reader supports Online PIN, the customer should be prompted for a PIN without a new transaction being created. If the card reader does not support Online PIN, the customer should try again by inserting their card and entering a PIN.', 'super-forms' )
        // tmp                 ),
        // tmp                 'pickup_card' => array(
        // tmp                     'desc' => esc_html__( 'The card cannot be used to make this payment (it is possible it has been reported lost or stolen).', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'pin_try_exceeded' => array(
        // tmp                     'desc' => esc_html__( 'The allowable number of PIN tries has been exceeded.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer must use another card or method of payment.', 'super-forms' )
        // tmp                 ),
        // tmp                 'processing_error' => array(
        // tmp                     'desc' => esc_html__( 'An error occurred while processing the card.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The payment should be attempted again. If it still cannot be processed, try again later.', 'super-forms' )
        // tmp                 ),
        // tmp                 'reenter_transaction' => array(
        // tmp                     'desc' => esc_html__( 'The payment could not be processed by the issuer for an unknown reason.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The payment should be attempted again. If it still cannot be processed, the customer needs to contact their card issuer.', 'super-forms' )
        // tmp                 ),
        // tmp                 'restricted_card' => array(
        // tmp                     'desc' => esc_html__( 'The card cannot be used to make this payment (it is possible it has been reported lost or stolen).', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'revocation_of_all_authorizations' => array(
        // tmp                     'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'revocation_of_authorization' => array(
        // tmp                     'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'security_violation' => array(
        // tmp                     'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'service_not_allowed' => array(
        // tmp                     'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'stolen_card' => array(
        // tmp                     'desc' => esc_html__( 'The payment has been declined because the card is reported stolen.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The specific reason for the decline should not be reported to the customer. Instead, it needs to be presented as a generic decline.', 'super-forms' )
        // tmp                 ),
        // tmp                 'stop_payment_order' => array(
        // tmp                     'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'testmode_decline' => array(
        // tmp                     'desc' => esc_html__( 'A Stripe test card number was used.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'A genuine card must be used to make a payment.', 'super-forms' )
        // tmp                 ),
        // tmp                 'transaction_not_allowed' => array(
        // tmp                     'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'try_again_later' => array(
        // tmp                     'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'Ask the customer to attempt the payment again. If subsequent payments are declined, the customer should contact their card issuer for more information.', 'super-forms' )
        // tmp                 ),
        // tmp                 'withdrawal_count_limit_exceeded' => array(
        // tmp                     'desc' => esc_html__( 'The customer has exceeded the balance or credit limit available on their card. ', 'super-forms' ),
        // tmp                     'steps' => esc_html__( 'The customer should use an alternative payment method.', 'super-forms' )
        // tmp                 )
        // tmp             )       
        // tmp         )
        // tmp     );
        // tmp     $scripts['currencyFormatter'] = array(
        // tmp         'src'     => plugin_dir_url( __FILE__ ) . 'currencyFormatter.min.js',
        // tmp         'deps'    => array('super-stripe-dashboard'),
        // tmp         'version' => SUPER_VERSION,
        // tmp         'footer'  => true,
        // tmp         'screen'  => array( 
        // tmp             'super-forms_page_super_stripe_dashboard'
        // tmp         ),
        // tmp         'method'  => 'enqueue'
        // tmp     );
        // tmp     if(isset($scripts['masked-currency'])){
        // tmp         $scripts['masked-currency']['screen'][] = 'super-forms_page_super_stripe_dashboard';
        // tmp     }

        // tmp     return $scripts;
        // tmp }


        public static function setAppInfo(){
            require_once 'stripe-php/init.php';
            \Stripe\Stripe::setAppInfo(
                'Super Forms - Stripe Add-on',
                SUPER_VERSION,
                'https://f4d.nl/super-forms'
            );
            $global_settings = SUPER_Common::get_global_settings();
            if(!empty($global_settings['stripe_mode']) ) {
                $global_settings['stripe_mode'] = 'sandbox';
            }else{
                $global_settings['stripe_mode'] = 'live';
            }
            $key = (isset($global_settings['stripe_' . $global_settings['stripe_mode'] . '_secret_key']) ? $global_settings['stripe_' . $global_settings['stripe_mode'] . '_secret_key'] : '');
            $version = (isset($global_settings['stripe_' . $global_settings['stripe_mode'] . '_api_version']) ? $global_settings['stripe_' . $global_settings['stripe_mode'] . '_api_version'] : '');
            \Stripe\Stripe::setApiKey($key);
            \Stripe\Stripe::setApiVersion($version);
        }
        
        // tmp public static function delete_list_views_filter($views){
        // tmp     if(!isset($views['trash'])) return array();
        // tmp     return array('trash' => $views['trash']);
        // tmp }

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

        // tmp /**
        // tmp  * Add Stripe styles
        // tmp  *
        // tmp  *  @since      1.0.0
        // tmp  */
        // tmp public static function add_stripe_styles($style_content, $atts) {
        // tmp     //$atts['id'] // form id
        // tmp     //$atts['settings'] // form settings
        // tmp     $styles = "
        // tmp     .super-stripe-ideal-element {
        // tmp         height: 33px;
        // tmp         width: 300px;
        // tmp     }
        // tmp     .super-field-size-large .super-stripe-ideal-element {
        // tmp         height: 43px;
        // tmp     }
        // tmp     .super-field-size-huge .super-stripe-ideal-element {
        // tmp         height: 53px;
        // tmp     }
        // tmp     .super-stripe-cc-element,
        // tmp     .super-stripe-iban-element {
        // tmp         padding-top: 8px;
        // tmp         padding-left: 15px;
        // tmp         padding-right: 0px;
        // tmp         padding-bottom: 8px;
        // tmp         height: 33px;
        // tmp         width: 300px;
        // tmp     }
        // tmp     .super-field-size-large .super-stripe-cc-element,
        // tmp     .super-field-size-large .super-stripe-iban-element {
        // tmp         padding-top: 13px;
        // tmp         padding-left: 15px;
        // tmp         padding-right: 0px;
        // tmp         padding-bottom: 13px;
        // tmp         height: 43px;
        // tmp     }
        // tmp     .super-field-size-huge .super-stripe-cc-element,
        // tmp     .super-field-size-huge .super-stripe-iban-element {
        // tmp         padding-top: 17px;
        // tmp         padding-left: 15px;
        // tmp         padding-right: 0px;
        // tmp         padding-bottom: 17px;
        // tmp         height: 53px;
        // tmp     }
        // tmp     .super-style-one .super-stripe-base:before {
        // tmp         content: '';
        // tmp         position: absolute;
        // tmp         left: 0;
        // tmp         width: 0%;
        // tmp         bottom: 1px;
        // tmp         margin-top: 2px;
        // tmp         bottom: -8px;
        // tmp         border-bottom: 4px solid #cdcdcd;
        // tmp         z-index: 2;
        // tmp         -webkit-transition: width .4s ease-out;
        // tmp         -moz-transition: width .4s ease-out;
        // tmp         -o-transition: width .4s ease-out;
        // tmp         transition: width .4s ease-out;
        // tmp     }
        // tmp     .super-style-one .super-stripe-focus:before {
        // tmp         width: 100%;
        // tmp     }";
        // tmp     return $style_content.$styles;
        // tmp }


        // tmp /**
        // tmp  * Create Stripe Customer (required for subscriptions)
        // tmp  *
        // tmp  *  @since      1.0.0
        // tmp  */
        // tmp public static function stripe_create_subscription() {
        // tmp     self::setAppInfo();
        // tmp     $data = $_POST['data'];
        // tmp     $payment_method = $_POST['payment_method'];
        // tmp     $metadata = ( isset($_POST['metadata']) ? $_POST['metadata'] : array() );
        // tmp     $form_id = absint($data['hidden_form_id']['value']);
        // tmp     $settings = SUPER_Common::get_form_settings($form_id);
        // tmp     
        // tmp     // Check if plan ID is empty (is required)
        // tmp     if(empty($settings['stripe_plan_id'])){
        // tmp         SUPER_Common::output_message( array(
        // tmp             'msg' => esc_html__( 'Subscription plan ID cannot be empty!', 'super-forms' ),
        // tmp             'form_id' => absint($form_id)
        // tmp         ));
        // tmp     }

        // tmp     // Check if the user is logged in
        // tmp     // If so, we will want to save the stripe customer ID for this wordpress user
        // tmp     $user_id = get_current_user_id();
        // tmp     if(isset($metadata['frontend_user_id'])){
        // tmp         if(absint($metadata['frontend_user_id'])!==0){
        // tmp             $user_id = $metadata['frontend_user_id'];
        // tmp         }
        // tmp     }
        // tmp     $super_stripe_cus = get_user_meta( $user_id, 'super_stripe_cus', true );

        // tmp     try {
        // tmp         $create_new_customer = true;
        // tmp         // Check if user is logged in, if so check if this is an existing customer
        // tmp         // If customer exists, we want to update the `default_payment_method` and `invoice_settings.default_payment_method`
        // tmp         if( !empty($super_stripe_cus) ) {
        // tmp             $customer = \Stripe\Customer::retrieve($super_stripe_cus);
        // tmp         }
        // tmp         if( !empty($customer) ) {
        // tmp             // Check if customer was deleted
        // tmp             if(!empty($customer['deleted']) && $customer['deleted']==true){
        // tmp                 // Customer was deleted, we should create a new
        // tmp             }else{
        // tmp                 // The customer exists, let's update the payment method for this customer
        // tmp                 $paymentMethod = \Stripe\PaymentMethod::retrieve($payment_method); // e.g: pm_1FYeznClCIKljWvssSbEXRww
        // tmp                 $paymentMethod->attach(['customer' => $customer->id]);
        // tmp                 // Once the payment method has been attached to your customer, 
        // tmp                 // update the customers default payment method
        // tmp                 \Stripe\Customer::update($customer->id,[
        // tmp                     'invoice_settings' => [
        // tmp                         'default_payment_method' => $paymentMethod->id,
        // tmp                     ],
        // tmp                 ]);
        // tmp                 // Make sure we do not create a new customer
        // tmp                 $create_new_customer = false; 
        // tmp             }
        // tmp         }
        // tmp         if($create_new_customer){
        // tmp             // Customer doesn't exists, create a new customer
        // tmp             $customer = \Stripe\Customer::create([
        // tmp                 'payment_method' => $payment_method,
        // tmp                 'email' => 'jenny.rosen@example.com',
        // tmp                 'invoice_settings' => [
        // tmp                     'default_payment_method' => $payment_method // Creating subscriptions automatically charges customers because the default payment method is set.
        // tmp                 ],
        // tmp             ]);
        // tmp             $paymentMethod->attach(['customer' => $customer->id]);
        // tmp             // Save the stripe customer ID for this wordpress user
        // tmp             update_user_meta( $user_id, 'super_stripe_cus', $customer->id);
        // tmp         }
        // tmp     } catch( Exception $e ){
        // tmp         if ($e instanceof \Stripe\Error\Card ||
        // tmp             $e instanceof \Stripe\Exception\CardException ||
        // tmp             $e instanceof \Stripe\Exception\RateLimitException ||
        // tmp             $e instanceof \Stripe\Exception\InvalidRequestException ||
        // tmp             $e instanceof \Stripe\Exception\AuthenticationException ||
        // tmp             $e instanceof \Stripe\Exception\ApiConnectionException ||
        // tmp             $e instanceof \Stripe\Exception\ApiErrorException) {
        // tmp             // Specific Stripe exception
        // tmp             if($e->getCode()===0){
        // tmp                 // If no such stripe customer exists we do not output the error instead we create a new stripe customer
        // tmp                 // Customer doesn't exists, create a new customer
        // tmp                 $customer = \Stripe\Customer::create([
        // tmp                     'payment_method' => $payment_method,
        // tmp                     'email' => 'jenny.rosen@example2.com',
        // tmp                     'invoice_settings' => [
        // tmp                         'default_payment_method' => $payment_method // Creating subscriptions automatically charges customers because the default payment method is set.
        // tmp                     ],
        // tmp                 ]);
        // tmp                 $paymentMethod->attach(['customer' => $customer->id]);
        // tmp                 // Save the stripe customer ID for this wordpress user
        // tmp                 update_user_meta( $user_id, 'super_stripe_cus', $customer->id);
        // tmp             }else{
        // tmp                 error_log("exceptionHandler10()");
        // tmp                 self::exceptionHandler($e, $metadata);
        // tmp             }
        // tmp         } else {
        // tmp             // Normal exception
        // tmp             error_log("normal exceptionHandler10()");
        // tmp             self::exceptionHandler($e, $metadata);
        // tmp         }
        // tmp     }

        // tmp     try {
        // tmp         // Attempt to create the subscriptions
        // tmp         $subscription = \Stripe\Subscription::create([
        // tmp             'customer' => $customer->id,
        // tmp             'items' => [
        // tmp                 [
        // tmp                     'plan' => $settings['stripe_plan_id'],
        // tmp                 ],
        // tmp             ],
        // tmp             // 'trial_period_days' => 0, // Integer representing the number of trial period days before the customer is charged for the first time. This will always overwrite any trials that might apply via a subscribed plan.
        // tmp             'payment_behavior' => 'allow_incomplete',
        // tmp             'expand' => ['latest_invoice.payment_intent'],
        // tmp             'metadata' => $metadata
        // tmp         ]);
        // tmp     } catch( Exception $e ){
        // tmp         if ($e instanceof \Stripe\Error\Card ||
        // tmp             $e instanceof \Stripe\Exception\CardException ||
        // tmp             $e instanceof \Stripe\Exception\RateLimitException ||
        // tmp             $e instanceof \Stripe\Exception\InvalidRequestException ||
        // tmp             $e instanceof \Stripe\Exception\AuthenticationException ||
        // tmp             $e instanceof \Stripe\Exception\ApiConnectionException ||
        // tmp             $e instanceof \Stripe\Exception\ApiErrorException) {
        // tmp             // Specific Stripe exception
        // tmp             error_log("specific exceptionHandler11()");
        // tmp             self::exceptionHandler($e, $metadata);
        // tmp         } else {
        // tmp             // Normal exception
        // tmp             error_log("normal exceptionHandler11()");
        // tmp             self::exceptionHandler($e, $metadata);
        // tmp         }
        // tmp     }

        // tmp     // Update PaymentIntent with metadata
        // tmp     \Stripe\PaymentIntent::update( $subscription->latest_invoice->payment_intent->id, array( 'metadata' => $metadata ) );

        // tmp     // Depending on the outcome do things:
        // tmp     $paymentintent_status = (isset($subscription->latest_invoice->payment_intent) ? $subscription->latest_invoice->payment_intent->status : '');

        // tmp     // Outcome 3: Payment fails
        // tmp     if (($subscription->status == 'incomplete') && ($subscription->latest_invoice->status == 'open') && ($paymentintent_status == 'requires_payment_method')) {
        // tmp         // The charge attempt for the subscription failed, please try with a new payment method
        // tmp         self::payment_intent_payment_failed( array( 'metadata' => $metadata ) );
        // tmp         SUPER_Common::output_message( array(
        // tmp             'msg' => esc_html__( 'The charge attempt for the subscription failed, please try with a new payment method', 'super-forms' ),
        // tmp             'form_id' => absint($form_id)
        // tmp         ));
        // tmp     }

        // tmp     echo json_encode( array( 
        // tmp         'client_secret' => $subscription->latest_invoice->payment_intent->client_secret,
        // tmp         'subscription_status' => $subscription->status,
        // tmp         'invoice_status' => $subscription->latest_invoice->status,
        // tmp         'paymentintent_status' => (isset($subscription->latest_invoice->payment_intent) ? $subscription->latest_invoice->payment_intent->status : ''),
        // tmp         'metadata' => $metadata
        // tmp     ) );

        // tmp     // // Outcome 1: Payment succeeds
        // tmp     // if (($subscription->status == 'active') && ($subscription->latest_invoice->status == 'paid') && ($paymentintent_status == 'succeeded')) {
        // tmp     //     //console.log('Payment succeeds');
        // tmp     //     // The payment has succeeded. Display a success message.
        // tmp     //     //console.log('The payment has succeeded, show success message1.');
        // tmp     //     //$form.data('is-doing-things', ''); // Finish form submission
        // tmp     // }
        // tmp     // // Outcome 2: Trial starts
        // tmp     // if (($subscription->status == 'trialing') && ($subscription->latest_invoice->status == 'paid')) {
        // tmp     //     //console.log('Trial starts');
        // tmp     //     //$form.data('is-doing-things', ''); // Finish form submission
        // tmp     // }

        // tmp     // // Outcome 4: Requires action
        // tmp     // if (($subscription->status == 'incomplete') && ($subscription->latest_invoice->status == 'open') && ($paymentintent_status == 'requires_action')) {
        // tmp     //     Notify customer that further action is required
        // tmp     //     stripe.confirmCardPayment(result.client_secret).then(function (result) {
        // tmp     //         if (result.error) {
        // tmp     //             // Display error.msg in your UI.
        // tmp     //             SUPER.stripe_proceed(result, $form, $oldHtml);
        // tmp     //         } else {
        // tmp     //             // The payment has succeeded. Display a success message.
        // tmp     //             console.log('The payment has succeeded, show success message2.');
        // tmp     //             $form.data('is-doing-things', ''); // Finish form submission
        // tmp     //         }
        // tmp     //     });
        // tmp     // }



        // tmp     // // Outcome 1: Payment succeeds
        // tmp     // if( ($subscription->status=='active') && ($subscription->latest_invoice->status=='paid') && ($subscription->latest_invoice->payment_intent->status=='succeeded') ) {
        // tmp     // }
        // tmp     // // Outcome 2: Trial starts
        // tmp     // if( ($subscription->status=='trialing') && ($subscription->latest_invoice->status=='paid') ) {
        // tmp     // }
        // tmp     // // Outcome 3: Payment fails
        // tmp     // if( ($subscription->status=='incomplete') && ($subscription->latest_invoice->status=='open') && ($subscription->latest_invoice->payment_intent->status=='requires_payment_method') ) {
        // tmp     // }
        // tmp     // // Outcome 4: Requires action
        // tmp     // if( ($subscription->status=='incomplete') && ($subscription->latest_invoice->status=='open') && ($subscription->latest_invoice->payment_intent->status=='requires_action') ) {
        // tmp     // }

        // tmp     die();
        // tmp }

        // tmp // Create PaymentIntent
        // tmp public static function createPaymentIntent($payment_method, $data, $settings, $amount, $currency, $description, $metadata){
        // tmp     //error_log("createPaymentIntent()");
        // tmp     //error_log("payment_method: " . $payment_method);
        // tmp     //error_log("amount: " . $amount);
        // tmp     //error_log("currency: " . $currency);
        // tmp     //error_log("description: " . $description);
        // tmp     try {
        // tmp         $data = array(
        // tmp             'amount' => $amount, // The amount to charge times hundred (because amount is in cents)
        // tmp             'currency' => ($payment_method==='ideal' || $payment_method==='sepa_debit' ? 'eur' : $currency), // iDeal only accepts "EUR" as a currency
        // tmp             'description' => $description,
        // tmp             'payment_method_types' => [$payment_method], // e.g: ['card','ideal','sepa_debit'], 
        // tmp             // Shipping information for this PaymentIntent.
        // tmp             'shipping' => array(
        // tmp                 'address' => array(
        // tmp                     'line1' => SUPER_Common::email_tags( $settings['stripe_line1'], $data, $settings ),
        // tmp                     'line2' => SUPER_Common::email_tags( $settings['stripe_line2'], $data, $settings ),
        // tmp                     'city' => SUPER_Common::email_tags( $settings['stripe_city'], $data, $settings ),
        // tmp                     'country' => SUPER_Common::email_tags( $settings['stripe_country'], $data, $settings ),
        // tmp                     'postal_code' => SUPER_Common::email_tags( $settings['stripe_postal_code'], $data, $settings ),
        // tmp                     'state' => SUPER_Common::email_tags( $settings['stripe_state'], $data, $settings )
        // tmp                 ),
        // tmp                 'name' => SUPER_Common::email_tags( $settings['stripe_name'], $data, $settings ),
        // tmp                 'phone' => SUPER_Common::email_tags( $settings['stripe_phone'], $data, $settings ),
        // tmp                 'carrier' => SUPER_Common::email_tags( $settings['stripe_carrier'], $data, $settings ),
        // tmp                 'tracking_number' => SUPER_Common::email_tags( $settings['stripe_tracking_number'], $data, $settings )
        // tmp             ),
        // tmp             'metadata' => $metadata
        // tmp         );
        // tmp         // Only add receipt email if E-mail address was set
        // tmp         if(!empty($settings['stripe_email'])){
        // tmp             $data['stripe_email'] = SUPER_Common::email_tags( $settings['stripe_email'], $data, $settings ); // E-mail address that the receipt for the resulting payment will be sent to.
        // tmp         }
        // tmp         if( $payment_method=='sepa_debit' ) {
        // tmp             $data['setup_future_usage'] = 'off_session'; // SEPA Direct Debit only accepts an off_session value for this parameter.
        // tmp         }
        // tmp         $intent = \Stripe\PaymentIntent::create($data);

        // tmp         //error_log('$payment_method 2: '. $payment_method, 0);
        // tmp         //$data = array(
        // tmp         //    'amount' => $amount, // The amount to charge times hundred (because amount is in cents)
        // tmp         //    'currency' => ($payment_method==='ideal' || $payment_method==='sepa_debit' ? 'eur' : $currency), // iDeal only accepts "EUR" as a currency
        // tmp         //    'description' => $description,
        // tmp         //    'payment_method_types' => [$payment_method], // e.g: ['card','ideal','sepa_debit'], 
        // tmp         //    'receipt_email' => SUPER_Common::email_tags( $settings['stripe_email'], $data, $settings ), // E-mail address that the receipt for the resulting payment will be sent to.
        // tmp         //    // Shipping information for this PaymentIntent.
        // tmp         //    'shipping' => array(
        // tmp         //        'address' => array(
        // tmp         //            'line1' => SUPER_Common::email_tags( $settings['stripe_line1'], $data, $settings ),
        // tmp         //            'line2' => SUPER_Common::email_tags( $settings['stripe_line2'], $data, $settings ),
        // tmp         //            'city' => SUPER_Common::email_tags( $settings['stripe_city'], $data, $settings ),
        // tmp         //            'country' => SUPER_Common::email_tags( $settings['stripe_country'], $data, $settings ),
        // tmp         //            'postal_code' => SUPER_Common::email_tags( $settings['stripe_postal_code'], $data, $settings ),
        // tmp         //            'state' => SUPER_Common::email_tags( $settings['stripe_state'], $data, $settings )
        // tmp         //        ),
        // tmp         //        'name' => SUPER_Common::email_tags( $settings['stripe_name'], $data, $settings ),
        // tmp         //        'phone' => SUPER_Common::email_tags( $settings['stripe_phone'], $data, $settings ),
        // tmp         //        'carrier' => SUPER_Common::email_tags( $settings['stripe_carrier'], $data, $settings ),
        // tmp         //        'tracking_number' => SUPER_Common::email_tags( $settings['stripe_tracking_number'], $data, $settings )
        // tmp         //    ),
        // tmp         //    'metadata' => $metadata
        // tmp         //);
        // tmp         //if( $payment_method=='sepa_debit' ) {
        // tmp         //    $data['setup_future_usage'] = 'off_session'; // SEPA Direct Debit only accepts an off_session value for this parameter.
        // tmp         //}
        // tmp         //$intent = \Stripe\PaymentIntent::create($data);
        // tmp         //error_log("intent:" . json_encode($intent));
        // tmp     } catch( Exception $e ){
        // tmp         if ($e instanceof \Stripe\Error\Card ||
        // tmp             $e instanceof \Stripe\Exception\CardException ||
        // tmp             $e instanceof \Stripe\Exception\RateLimitException ||
        // tmp             $e instanceof \Stripe\Exception\InvalidRequestException ||
        // tmp             $e instanceof \Stripe\Exception\AuthenticationException ||
        // tmp             $e instanceof \Stripe\Exception\ApiConnectionException ||
        // tmp             $e instanceof \Stripe\Exception\ApiErrorException) {
        // tmp             // Specific Stripe exception
        // tmp             error_log("specific exceptionHandler15()");
        // tmp             self::exceptionHandler($e, $metadata);
        // tmp         } else {
        // tmp             // Normal exception
        // tmp             error_log("normal exceptionHandler15()");
        // tmp             self::exceptionHandler($e, $metadata);
        // tmp         }
        // tmp     }
        // tmp     return $intent;
        // tmp }



        // tmp /**
        // tmp  * Change row actions
        // tmp  *
        // tmp  *  @since      1.0.0
        // tmp  */
        // tmp public static function remove_row_actions( $actions ) {
        // tmp     if( (get_post_type()==='super_stripe_txn') || (get_post_type()==='super_stripe_sub') ) {
        // tmp         if( isset( $actions['trash'] ) ) {
        // tmp             unset( $actions['trash'] );
        // tmp         }
        // tmp         unset( $actions['inline hide-if-no-js'] );
        // tmp         unset( $actions['view'] );
        // tmp         unset( $actions['edit'] );
        // tmp     }
        // tmp     return $actions;
        // tmp }


        public static function stripe_element_scripts() {
            wp_enqueue_script( 'stripe-v3', '//js.stripe.com/v3/', array(), SUPER_VERSION, false );
            $handle = 'super-stripe';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'stripe-elements.js', array( 'stripe-v3', 'jquery', 'super-common' ), SUPER_VERSION, false );  
            $global_settings = SUPER_Common::get_global_settings();
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
                    'stripe_pk' => (isset($global_settings['stripe_pk']) ? $global_settings['stripe_pk'] : ''), 
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


        // tmp /**
        // tmp  * Update transaction counter and enqueue Stripe element scripts
        // tmp  *
        // tmp  * @param  string $current_screen
        // tmp  * 
        // tmp  * @since       1.0.0
        // tmp */
        // tmp public function after_screen( $current_screen ) {
        // tmp     if( $current_screen->id=='super-forms_page_super_stripe_dashboard' ) {
        // tmp         //update_option( 'super_stripe_txn_count', 0 );
        // tmp     } 
        // tmp     if( $current_screen->id=='super-forms_page_super_create_form' ) {
        // tmp         //self::stripe_element_scripts();
        // tmp     }
        // tmp }


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


        // tmp /**
        // tmp  *  Register post types
        // tmp  *
        // tmp  *  @since    1.0.0
        // tmp  */
        // tmp public static function register_post_types() {
        // tmp     if (!post_type_exists('super_stripe_txn')) {
        // tmp         register_post_type('super_stripe_txn', apply_filters('super_register_post_type_super_stripe_txn', array(
        // tmp             'label' => 'Stripe Transactions',
        // tmp             'description' => '',
        // tmp             'capability_type' => 'post',
        // tmp             'exclude_from_search' => true, // make sure to exclude from default search
        // tmp             'public' => false,
        // tmp             'query_var' => false,
        // tmp             'has_archive' => false,
        // tmp             'publicaly_queryable' => false,
        // tmp             'show_ui' => true,
        // tmp             'show_in_menu' => false,
        // tmp             'map_meta_cap' => true,
        // tmp             'hierarchical' => false,
        // tmp             'supports' => array(),
        // tmp             'capabilities' => array(
        // tmp                 'create_posts' => false, // Removes support for the "Add New" function
        // tmp             ),
        // tmp             'rewrite' => array(
        // tmp                 'slug' => 'super_stripe_txn',
        // tmp                 'with_front' => true
        // tmp             ),
        // tmp             'labels' => array(
        // tmp                 'name' => 'Stripe Transactions',
        // tmp                 'singular_name' => 'Stripe Transaction',
        // tmp                 'menu_name' => 'Stripe Transactions',
        // tmp                 'add_new' => 'Add Transaction',
        // tmp                 'add_new_item' => 'Add New Transaction',
        // tmp                 'edit' => 'Edit',
        // tmp                 'edit_item' => 'Edit Transaction',
        // tmp                 'new_item' => 'New Transaction',
        // tmp                 'view' => 'View Transaction',
        // tmp                 'view_item' => 'View Transaction',
        // tmp                 'search_items' => 'Search Transactions',
        // tmp                 'not_found' => 'No Transactions Found',
        // tmp                 'not_found_in_trash' => 'No Transactions Found in Trash',
        // tmp                 'parent' => 'Parent Transaction',
        // tmp             )
        // tmp         )));
        // tmp     }
        // tmp     if (!post_type_exists('super_stripe_sub')) {
        // tmp         register_post_type('super_stripe_sub', apply_filters('super_register_post_type_super_stripe_sub', array(
        // tmp             'label' => 'Stripe Subscriptions',
        // tmp             'description' => '',
        // tmp             'capability_type' => 'post',
        // tmp             'exclude_from_search' => true, // make sure to exclude from default search
        // tmp             'public' => false,
        // tmp             'query_var' => false,
        // tmp             'has_archive' => false,
        // tmp             'publicaly_queryable' => false,
        // tmp             'show_ui' => true,
        // tmp             'show_in_menu' => false,
        // tmp             'map_meta_cap' => true,
        // tmp             'hierarchical' => false,
        // tmp             'supports' => array(),
        // tmp             'capabilities' => array(
        // tmp                 'create_posts' => false, // Removes support for the "Add New" function
        // tmp             ),
        // tmp             'rewrite' => array(
        // tmp                 'slug' => 'super_stripe_sub',
        // tmp                 'with_front' => true
        // tmp             ),
        // tmp             'labels' => array(
        // tmp                 'name' => 'Stripe Subscriptions',
        // tmp                 'singular_name' => 'Stripe Subscription',
        // tmp                 'menu_name' => 'Stripe Subscriptions',
        // tmp                 'add_new' => 'Add Subscription',
        // tmp                 'add_new_item' => 'Add New Subscription',
        // tmp                 'edit' => 'Edit',
        // tmp                 'edit_item' => 'Edit Subscription',
        // tmp                 'new_item' => 'New Subscription',
        // tmp                 'view' => 'View Subscription',
        // tmp                 'view_item' => 'View Subscription',
        // tmp                 'search_items' => 'Search Subscriptions',
        // tmp                 'not_found' => 'No Subscriptions Found',
        // tmp                 'not_found_in_trash' => 'No Subscriptions Found in Trash',
        // tmp                 'parent' => 'Parent Subscription',
        // tmp             )
        // tmp         )));
        // tmp     }
        // tmp }

               
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
                'Stripe',
                '<span class="super-stripe-icon" style="' . $styles . '"></span>Stripe' . $count,
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
                // tmp require_once('dashboard.php');
            }
        }
        public static function getInvoice($id) {
            return \Stripe\Invoice::retrieve($id);
        }
        public static function exceptionHandler($e, $metadata=array()){
            //error_log("e: " . json_encode($e));
            //error_log("err: " . json_encode($e->getError()));
            //error_log("metadata: " . json_encode($metadata));
            $form_id = SUPER_Common::cleanupFormSubmissionInfo($metadata['sfsi_id'], '');
            //error_log("form_id 1: " . $form_id);
            if(isset($metadata['sf_id'])) $form_id = $metadata['sf_id'];
            //error_log("form_id 2: " . $form_id);
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
                        //error_log('Product ID: ' . $vv->plan->product, 0);
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



        // tmp /**
        // tmp  * Load More 
        // tmp  *
        // tmp  *  @since      1.0.0
        // tmp  */
        // tmp public static function super_stripe_api_handler() {  
        // tmp     if( !empty($_POST['data']) ) {
        // tmp         $data = $_POST['data'];
        // tmp         if( !empty($data['type']) ) {
        // tmp             self::setAppInfo();
        // tmp             $type = sanitize_text_field($data['type']);
        // tmp             if( $type=='searchUsers' ) {
        // tmp                 // Search WordPress users
        // tmp                 $value = sanitize_text_field($data['value']);
        // tmp                 // We use this to give a "best matches/suggestions" user connection for this customer
        // tmp                 $customer_email = sanitize_email($data['customer_email']);
        // tmp                 if(empty($customer_email)){
        // tmp                     $suggestions = array();
        // tmp                 }else{
        // tmp                     // Try to search for suggestions
        // tmp                     $query = new WP_User_Query(
        // tmp                         array(
        // tmp                             'fields' => array(
        // tmp                                 'ID',
        // tmp                                 'user_login',
        // tmp                                 'display_name',
        // tmp                                 'user_email'
        // tmp                             ),
        // tmp                             'number' => 1, // Acts as the limit
        // tmp                             'search' => "*{$customer_email}*",
        // tmp                             'search_columns' => array(
        // tmp                                 'user_email'
        // tmp                             ),
        // tmp                         )
        // tmp                     );
        // tmp                     $suggestions = $query->get_results();
        // tmp                 }
        // tmp                 $exclude = array();
        // tmp                 if(isset($suggestions[0])){
        // tmp                     $exclude = array($suggestions[0]->ID);
        // tmp                 }
        // tmp                 // search usertable
        // tmp                 $query = new WP_User_Query(
        // tmp                     array(
        // tmp                         'fields' => array(
        // tmp                             'ID',
        // tmp                             'user_login',
        // tmp                             'display_name',
        // tmp                             'user_email'
        // tmp                         ),
        // tmp                         'number' => 20, // Acts as the limit
        // tmp                         'exclude' => $exclude, // Exclude suggested user
        // tmp                         'search' => "*{$value}*",
        // tmp                         'search_columns' => array(
        // tmp                             'ID',
        // tmp                             'user_login',
        // tmp                             'display_name',
        // tmp                             'user_email',
        // tmp                             'user_nicename'
        // tmp                         ),
        // tmp                     )
        // tmp                 );
        // tmp                 $users = $query->get_results();
        // tmp                 // search usermeta
        // tmp                 $query = new WP_User_Query(
        // tmp                     array(
        // tmp                         'fields' => array(
        // tmp                             'ID',
        // tmp                             'user_login',
        // tmp                             'display_name',
        // tmp                             'user_email'
        // tmp                         ),
        // tmp                         'number' => 20, // Acts as the limit
        // tmp                         'exclude' => $exclude, // Exclude suggested user
        // tmp                         'meta_query' => array(
        // tmp                             'relation' => 'OR',
        // tmp                             array(
        // tmp                                 'key' => 'first_name',
        // tmp                                 'value' => $value,
        // tmp                                 'compare' => 'LIKE'
        // tmp                             ),
        // tmp                             array(
        // tmp                                 'key' => 'last_name',
        // tmp                                 'value' => $value,
        // tmp                                 'compare' => 'LIKE'
        // tmp                             )
        // tmp                         )
        // tmp                     )
        // tmp                 );
        // tmp                 $users2 = $query->get_results();
        // tmp                 // Merge all users
        // tmp                 $users = array_merge( $users, $users2 );
        // tmp                 $payload = array(
        // tmp                     'suggestions' => $suggestions,
        // tmp                     'users' => array_unique($users, SORT_REGULAR)
        // tmp                 );
        // tmp             }
        // tmp             if( $type=='connectUser' ) {
        // tmp                 $unconnect = filter_var($data['unconnect'], FILTER_VALIDATE_BOOLEAN);
        // tmp                 $customer_id = sanitize_text_field($data['customer_id']);
        // tmp                 $user_id = sanitize_text_field($data['user_id']);
        // tmp                 if($unconnect==true){
        // tmp                     delete_user_meta( $user_id, 'super_stripe_cus');
        // tmp                     $payload = array();
        // tmp                 }else{
        // tmp                     update_user_meta( $user_id, 'super_stripe_cus', $customer_id);
        // tmp                     $edit_link = '#';
        // tmp                     if($user_id!==0){
        // tmp                         $user_info = get_userdata($user_id);
        // tmp                         if($user_info){
        // tmp                             $user_info->data->edit_link = get_edit_user_link($user_info->ID);
        // tmp                             $user_info->data->customer_id = $customer_id;
        // tmp                             $wp_user_info = $user_info->data;
        // tmp                         }
        // tmp                     }
        // tmp                     $payload = array(
        // tmp                         'wp_user_info' => $wp_user_info
        // tmp                     );
        // tmp                 }
        // tmp             }
        // tmp             if( $type=='invoice.online' || 
        // tmp                 $type=='invoice.pdf' || 
        // tmp                 $type=='paymentIntents' || 
        // tmp                 $type=='refreshPaymentIntent' ||
        // tmp                 $type=='customers' ||
        // tmp                 $type=='subscriptions'
        // tmp                 //$type=='products' || 
        // tmp                 ) {

        // tmp                     $payload = array();
        // tmp                     $id = '';
        // tmp                     $starting_after = null;
        // tmp                     if(empty($data['formatted'])) $formatted = true;
        // tmp                     if(!empty($data['limit'])) $limit = absint($data['limit']);
        // tmp                     if(!empty($data['id'])) $id = sanitize_text_field($data['id']);
        // tmp                     if(!empty($data['starting_after'])) $starting_after = sanitize_text_field($data['starting_after']);
        // tmp                     if( (!empty($id)) && (($type=='invoice.pdf') || ($type=='invoice.online')) ) {
        // tmp                         $payload = self::getInvoice($id);
        // tmp                     }
        // tmp                     if( $type=='paymentIntents' || $type=='refreshPaymentIntent' ) {
        // tmp                         if( (!empty($id)) && (($type=='refreshPaymentIntent')) ) {
        // tmp                             $starting_after = $id;
        // tmp                         }
        // tmp                         $payload = self::getPaymentIntents($formatted, $limit, $starting_after);
        // tmp                     }
        // tmp                     if( $type=='customers' ) {
        // tmp                         $payload = self::getCustomers($formatted, $limit, $starting_after);
        // tmp                     }
        // tmp                     if( $type=='subscriptions' ) {
        // tmp                         $payload = self::getSubscriptions($formatted, $limit, $starting_after);
        // tmp                     }
        // tmp                     // if( $type=='products' ) {
        // tmp                     //     $payload = self::getProducts($formatted, $limit, $starting_after);
        // tmp                     // }

        // tmp             }
        // tmp             if( $type=='refund.create' ) {
        // tmp                 // ID of the PaymentIntent to refund.
        // tmp                 $payment_intent = sanitize_text_field($data['payment_intent']);
        // tmp                 // String indicating the reason for the refund. If set, possible values are duplicate, fraudulent, and requested_by_customer.
        // tmp                 // If you believe the charge to be fraudulent, specifying fraudulent as the reason will add the associated card and email to your block lists, and will also help us improve our fraud detection algorithms.
        // tmp                 $reason = sanitize_text_field($data['reason']);
        // tmp                 // A positive integer in cents representing how much of this charge to refund.
        // tmp                 // Can refund only up to the remaining, unrefunded amount of the charge.
        // tmp                 $amount = sanitize_text_field($data['amount']);
        // tmp                 $payload = self::createRefund($payment_intent, $reason, $amount);
        // tmp             }
        // tmp             $payload = json_encode($payload);
        // tmp             echo $payload;
        // tmp         }
        // tmp     }
        // tmp     die();
        // tmp }    


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
        // no longer used...?                         'msg' => sprintf( esc_html__( 'Stripe Sandbox API key not configured, please enter your API key under %sSuper Forms > Settings > Stripe Checkout%s', 'super-forms' ), '<a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#stripe-checkout') . '">', '</a>' )
        // no longer used...?                     ));
        // no longer used...?                 }
        // no longer used...?                 if( (empty($global_settings['stripe_mode'])) && (empty($global_settings['stripe_live_key'])) ) {
        // no longer used...?                     SUPER_Common::output_message( array(
        // no longer used...?                         'msg' => sprintf( esc_html__( 'Stripe Live API key not configured, please enter your API key under %sSuper Forms > Settings > Stripe Checkout%s', 'super-forms' ), '<a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#stripe-checkout') . '">', '</a>' )
        // no longer used...?                     ));
        // no longer used...?                 }
        // no longer used...?                 // Check if iDeal element exists
        // no longer used...?                 if( !isset($data['stripe_ideal']) ) {
        // no longer used...?                     SUPER_Common::output_message( array(
        // no longer used...?                         'msg' => sprintf( esc_html__( 'No element found named %sstripe_ideal%s. Please make sure you added the Stripe iDeal element and named it %sstripe_ideal%s.', 'super-forms' ), '<strong>', '</strong>', '<strong>', '</strong>' )
        // no longer used...?                     ));
        // no longer used...?                 }else{
        // no longer used...?                     SUPER_Common::output_message( array(
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

        public static function fulfillOrder($x){
            error_log('fullfillOrder()');
            extract( shortcode_atts( array( 
                'form_id'=>0,
                's'=>array(),
                'stripe_session'=>array(), 
                'submissionInfo'=>array()
            ), $x));
            $settings = SUPER_Common::get_form_settings($form_id);
            $data = $submissionInfo['data'];
            $entry_id = absint($submissionInfo['entry_id']);
            $registered_user_id = absint($submissionInfo['registered_user_id']);
            $created_post = absint($submissionInfo['created_post']);
            $payment_intent = $stripe_session['payment_intent'];
            $invoice = $stripe_session['invoice'];
            $customer = $stripe_session['customer'];
            $subscription = $stripe_session['subscription'];
            error_log('stripe_session: ' . json_encode($stripe_session));
            error_log('payment_intent: ' . $payment_intent);
            error_log('invoice: ' . $invoice);
            error_log('customer: ' . $customer);
            error_log('subscription: ' . $subscription);
            if(!empty($entry_id)){
                // Update entry status after payment completed?
                $s['update_entry_status'] = SUPER_Common::email_tags(trim($s['update_entry_status']), $data, $settings);
                error_log('update entry status: ' . $s['update_entry_status']);
                // Update contact entry status after succesfull payment
                if(!empty($s['update_entry_status'])) update_post_meta($entry_id, '_super_contact_entry_status', $s['update_entry_status']);
                error_log('connect stripe payment intent to entry: ' . $payment_intent);
                // Connect Stripe details to this entry
                // @TODO --- === > TEST BELOW
                update_post_meta($entry_id, '_super_stripe_connections', 
                    array(
                        'payment_intent' => $payment_intent,
                        'invoice' => $invoice,
                        'customer' => $customer,
                        'subscription' => $subscription
                    )
                );
            }
            if(!empty($registered_user_id)){
                // Update registered user login status after payment completed?
                $s['register_login']['update_login_status'] = SUPER_Common::email_tags(trim($s['register_login']['update_login_status']), $data, $settings);
                if(!empty($s['register_login']['update_login_status'])){
                    error_log('update registered user login status: ' . $s['register_login']['update_login_status']);
                    // Update login status
                    if(!empty($s['register_login']['update_login_status'])) update_user_meta($registered_user_id, 'super_user_login_status', $s['register_login']['update_login_status']);
                }
                // Update registered user role after payment completed?
                $s['register_login']['update_user_role'] = SUPER_Common::email_tags(trim($s['register_login']['update_user_role']), $data, $settings);
                if(!empty($s['register_login']['update_user_role'])){
                    error_log('update registered user role: ' . $s['register_login']['update_user_role']);
                    // Update user role
                    $userdata = array('ID' => $registered_user_id, 'role' => $s['register_login']['update_user_role']);
                    wp_update_user($userdata);
                }
            }
            if(!empty($created_post)){
                $s['frontend_posting']['update_post_status'] = SUPER_Common::email_tags(trim($s['frontend_posting']['update_post_status']), $data, $settings);
                if(!empty($s['frontend_posting']['update_post_status'])){
                    // Update created post status after payment completed?
                    error_log('update post status: ' . $s['frontend_posting']['update_post_status']);
                    wp_update_post(array('ID' => $created_post, 'post_status' => $s['frontend_posting']['update_post_status']));
                }
            }
        }

        // tmp /**
        // tmp  * Hook into elements and add Stripe element
        // tmp  *
        // tmp  *  @since      1.0.0
        // tmp */
        // tmp public static function add_stripe_element( $array, $attributes ) {
        // tmp     // Include the predefined arrays
        // tmp     require( SUPER_PLUGIN_DIR . '/includes/shortcodes/predefined-arrays.php' );
        // tmp     $array['form_elements']['shortcodes']['stripe'] = array(
        // tmp         'callback' => 'SUPER_Stripe::stripe_element',
        // tmp         'name' => 'Stripe',
        // tmp         'icon' => 'stripe;fab',
        // tmp         'atts' => array(
        // tmp             'general' => array(
        // tmp                 'name' => esc_html__( 'General', 'super-forms' ),
        // tmp                 'fields' => array(
        // tmp                     'payment_method' => array(
        // tmp                         'name' => esc_html__( 'Choose payment gateway', 'super-forms' ), 
        // tmp                         'label' => esc_html__( 'Please note that the iDeal gateway can not be used in combination with subscriptions!', 'super-forms' ),
        // tmp                         'type' => 'select',
        // tmp                         'values' => array(
        // tmp                             'card' => esc_html__( 'Credit Card', 'super-forms' ),
        // tmp                             'sepa_debit' => esc_html__( 'SEPA Direct Debit', 'super-forms' ),
        // tmp                             'ideal' => esc_html__( 'iDeal', 'super-forms' ) 
        // tmp                         ),
        // tmp                         'default' => ( !isset( $attributes['payment_method'] ) ? '' : $attributes['payment_method'] )
        // tmp                     ),
        // tmp                     'label' => $label,
        // tmp                     'description'=>$description,
        // tmp                     'tooltip' => $tooltip
        // tmp                 ),
        // tmp             ),
        // tmp             'icon' => array(
        // tmp                 'name' => esc_html__( 'Icon', 'super-forms' ),
        // tmp                 'fields' => array(
        // tmp                     'icon_position' => $icon_position,
        // tmp                     'icon_align' => $icon_align,
        // tmp                     'icon' => SUPER_Shortcodes::icon($attributes,''),
        // tmp                 ),
        // tmp             ),
        // tmp             'conditional_logic' => $conditional_logic_array
        // tmp         )
        // tmp     );
        // tmp     return $array;
        // tmp }


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
                    'stripe_pk' => (isset($global_settings['stripe_pk']) ? $global_settings['stripe_pk'] : ''), 
                    'styles' => array(
                        
                    )
                )
            );
            wp_enqueue_script( $handle );

            $result = SUPER_Shortcodes::opening_tag(array('tag'=>$tag, 'atts'=>$atts, 'settings'=>$settings));
            $result .= SUPER_Shortcodes::opening_wrapper( $atts, $inner, $shortcodes, $settings );
            $result .= '<input hidden class="super-shortcode-field super-hidden" data-validation="empty" type="text" name="super_stripe_ideal" style="display:none;"';
            $result .= SUPER_Shortcodes::common_attributes( $atts, $tag );
            $result .= ' />';
            $result .= '<div class="super-stripe-ideal-element"></div>';
            $result .= '<div class="super-ideal-errors" role="alert"></div>';
            $result .= '</div>';
            $result .= SUPER_Shortcodes::loop_conditions( $atts, $tag, $settings );
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
            $result = SUPER_Shortcodes::opening_tag(array('tag'=>'text', 'atts'=>$atts, 'settings'=>$settings));
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
            $result .= SUPER_Shortcodes::loop_conditions( $atts, $tag, $settings );
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
                    'stripe_pk' => (isset($global_settings['stripe_pk']) ? $global_settings['stripe_pk'] : ''), 
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

            $result = SUPER_Shortcodes::opening_tag(array('tag'=>'text', 'atts'=>$atts, 'settings'=>$settings));
            $result .= SUPER_Shortcodes::opening_wrapper( $atts, $inner, $shortcodes, $settings );
            $result .= '<div class="super-stripe-ideal-element">';
            $result .= '</div>';
            $result .= '<!-- Used to display form errors. -->';
            $result .= '<div class="super-stripe-errors" role="alert"></div>';
            $result .= SUPER_Shortcodes::common_attributes( $atts, 'text' );
            $result .= '</div>';
            $result .= SUPER_Shortcodes::loop_conditions( $atts, $tag, $settings );
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
                    'stripe_pk' => (isset($global_settings['stripe_pk']) ? $global_settings['stripe_pk'] : ''), 
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

            $result = SUPER_Shortcodes::opening_tag(array('tag'=>'text', 'atts'=>$atts, 'settings'=>$settings));
            $result .= SUPER_Shortcodes::opening_wrapper( $atts, $inner, $shortcodes, $settings );
            $result .= '<div class="super-stripe-cc-element">';
            $result .= '</div>';
            $result .= '<!-- Used to display form errors. -->';
            $result .= '<div class="super-stripe-errors" role="alert"></div>';
            $result .= SUPER_Shortcodes::common_attributes( $atts, 'text' );
            $result .= '</div>';
            $result .= SUPER_Shortcodes::loop_conditions( $atts, $tag, $settings );
            $result .= '</div>';
            return $result;                 
        }


        // tmp /**
        // tmp  * Enqueue scripts
        // tmp  *
        // tmp  *  @since      1.0.0
        // tmp */
        // tmp public function enqueue_scripts() {
        // tmp     if( !empty($_GET['client_secret']) ) {
        // tmp         $client_secret = sanitize_text_field($_GET['client_secret']);
        // tmp         $livemode = sanitize_text_field($_GET['livemode']);
        // tmp         $source = sanitize_text_field($_GET['source']);
        // tmp         // Get Source status
        // tmp         // https://f4d.nl/dev/?client_secret=src_client_secret_FAjQj85HSzhwvo4EzUTgC4dm&livemode=false&source=src_1EgNxUFKn7uROhgClC0MmsoJ
        // tmp         $url = 'https://api.stripe.com/v1/sources/' . $source;
        // tmp         $response = wp_remote_post( 
        // tmp             $url, 
        // tmp             array(
        // tmp                 'timeout' => 45,
        // tmp                 'headers'=>array(
        // tmp                     'Authorization' => 'Bearer sk_test_CczNHRNSYyr4TenhiCp7Oz05'
        // tmp                 ),                      
        // tmp                 'body' => array()
        // tmp             )
        // tmp         );
        // tmp         if ( is_wp_error( $response ) ) {
        // tmp             $error_message = $response->get_error_message();
        // tmp             $GLOBALS['stripe_error_message'] = $error_message;
        // tmp         } else {
        // tmp             $obj = json_decode($response['body']);
        // tmp             $GLOBALS['stripe_obj'] = $obj;
        // tmp         }

        // tmp         // Enqueue styles
        // tmp         wp_enqueue_style( 'super-stripe-confirmation', plugin_dir_url( __FILE__ ) . 'stripe-confirmation.css', array(), SUPER_VERSION );
        // tmp         // Enqueue scripts
        // tmp         wp_enqueue_script( 'stripe-v3', '//js.stripe.com/v3/', array(), SUPER_VERSION, false ); 
        // tmp         $handle = 'super-stripe-confirmation';
        // tmp         $name = str_replace( '-', '_', $handle ) . '_i18n';
        // tmp         wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'confirmation.js', array(), SUPER_VERSION, false ); 
        // tmp         $global_settings = SUPER_Common::get_global_settings();
        // tmp         wp_localize_script(
        // tmp             $handle,
        // tmp             $name,
        // tmp             array( 
        // tmp                 'stripe_pk' => (isset($global_settings['stripe_pk']) ? $global_settings['stripe_pk'] : ''), 
        // tmp                 'status' => (!empty($GLOBALS['stripe_obj']) ? $GLOBALS['stripe_obj']->status : ''),
        // tmp                 'client_secret' => $client_secret,
        // tmp                 'livemode' => $livemode,
        // tmp                 'source' => $source,

        // tmp                 'chargeable' => esc_html__( 'Completing your order...', 'super_forms' ),
        // tmp                 'consumed' => sprintf( 
        // tmp                     esc_html__( '%sThank you for your order!%s%sWe’ll send your receipt as soon as your payment is confirmed.%s', 'super_forms' ), 
        // tmp                     '<div class="title">', 
        // tmp                     '</div>', 
        // tmp                     '<div class="description">', 
        // tmp                     '</div>' 
        // tmp                 ),
        // tmp                 'pending' => sprintf( 
        // tmp                     esc_html__( '%sPending payment!%s%sYour payment might be processed within a couple of days depending on your payment method.%s', 'super_forms' ), 
        // tmp                     '<div class="title">', 
        // tmp                     '</div>', 
        // tmp                     '<div class="description">', 
        // tmp                     '</div>' 
        // tmp                 ),
        // tmp                 'canceled' => sprintf( 
        // tmp                     esc_html__( '%sPayment canceled!%s', 'super_forms' ), 
        // tmp                     '<div class="title">', 
        // tmp                     '</div>'
        // tmp                 ),
        // tmp                 'failed' => sprintf( 
        // tmp                     esc_html__( '%sPayment failed!%s%sWe couldn’t process your order.%s', 'super_forms' ), 
        // tmp                     '<div class="title">', 
        // tmp                     '</div>', 
        // tmp                     '<div class="description">', 
        // tmp                     '</div>' 
        // tmp                 )


        // tmp             )
        // tmp         );
        // tmp         wp_enqueue_script( $handle );
        // tmp     }
        // tmp }


        // tmp /**
        // tmp  * Hook into JS filter and add the Stripe Token
        // tmp  *
        // tmp  *  @since      1.0.0
        // tmp */
        // tmp public static function add_dynamic_function( $functions ) {
        // tmp     $functions['before_submit_hook'][] = array(
        // tmp         'name' => 'stripe_validate'
        // tmp     );
        // tmp     $functions['after_email_send_hook'][] = array(
        // tmp         'name' => 'stripe_cc_create_payment_method'
        // tmp     );
        // tmp     $functions['after_email_send_hook'][] = array(
        // tmp         'name' => 'stripe_iban_create_payment_method'
        // tmp     );
        // tmp     $functions['after_email_send_hook'][] = array(
        // tmp         'name' => 'stripe_ideal_create_payment_method'
        // tmp     );

        // tmp     $functions['after_init_common_fields'][] = array(
        // tmp         'name' => 'init_stripe_elements'
        // tmp     );
        // tmp     return $functions;
        // tmp }


        /**
         * Hook into settings and add Stripe settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $x ) {
            //$statuses = SUPER_Settings::get_entry_statuses();
            //$new_statuses = array();
            //foreach($statuses as $k => $v) {
            //    $new_statuses[$k] = $v['name'];
            //}
            //$statuses = $new_statuses;
            //unset($new_statuses);
            $domain = home_url(); // e.g: 'http://domain.com';
            $home_url = trailingslashit($domain);
            $webhookUrl = $home_url . 'sfstripe/webhook'; // super forms stripe webhook e.g: https://domain.com/sfstripe/webhook will be converted into https://domain.com/index.php?sfstripewebhook=true 
            // Stripe Settings
            $array['stripe_checkout'] = array(        
                'hidden' => true,
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
                        'desc' => '<a target="_blank" href="https://dashboard.stripe.com/test/apikeys">' . esc_html__( 'Get your API key', 'super-forms' ) . '</a>',
                        'placeholder' => 'pk_test_XXXXXXXXXXXXXXXXXXXXXXXX',
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_mode',
                        'filter_value' => 'sandbox'
                    ),
                    'stripe_sandbox_secret_key' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Sandbox secret key', 'super-forms' ),
                        'desc' => '<a target="_blank" href="https://dashboard.stripe.com/test/apikeys">' . esc_html__( 'Get your API key', 'super-forms' ) . '</a>',
                        'placeholder' => 'sk_test_XXXXXXXXXXXXXXXXXXXXXXXX',
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_mode',
                        'filter_value' => 'sandbox'
                    ),
                    'stripe_sandbox_api_version' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Sandbox API version', 'super-forms' ),
                        'desc' => esc_html__( 'Enter the API version', 'super-forms' ) . ' (e.g: 2022-11-15)',
                        'placeholder' => 'YYYY-MM-DD',
                        'default' =>  '2022-11-15',
                        'filter' => true,
                        'parent' => 'stripe_mode',
                        'filter_value' => 'sandbox'
                    ),
                    'stripe_sandbox_webhook_id' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Sandbox webhook ID', 'super-forms' ),
                        'label' => sprintf( esc_html__( '%sCreate a webhook ID%s%sEndpoint URL must be: %s', 'super-forms' ), '<a target="_blank" href="https://dashboard.stripe.com/test/webhooks">', '</a>', '<br /><br />', '<code>' . $webhookUrl . '</code>' ),
                        'desc' => sprintf( 
                            esc_html__( 'Make sure the following events are enabled for this webhook:%s%s%s%s%s', 'super-forms' ), 
                            '<br />',
                            '<code>checkout.session.async_payment_failed</code></br />',
                            '<code>checkout.session.async_payment_succeeded</code></br />',
                            '<code>checkout.session.completed</code></br />',
                            '<code>checkout.session.expired</code></br />'
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
                        'desc' => '<a target="_blank" href="https://dashboard.stripe.com/test/webhooks">' . esc_html__( 'Get your webhook signing secret key', 'super-forms' ) . '</a>',
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
                        'filter' => true,
                        'parent' => 'stripe_mode',
                        'filter_value' => '',
                        'force_save' => true // if conditionally hidden, still store/save the value
                    ),
                    'stripe_live_secret_key' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Live secret key', 'super-forms' ),
                        'desc' => '<a target="_blank" href="https://dashboard.stripe.com/apikeys">' . esc_html__( 'Get your API key', 'super-forms' ) . '</a>',
                        'placeholder' => 'sk_live_XXXXXXXXXXXXXXXXXXXXXXXX',
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_mode',
                        'filter_value' => '',
                        'force_save' => true // if conditionally hidden, still store/save the value
                    ),
                    'stripe_live_api_version' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Live API version', 'super-forms' ),
                        'desc' => esc_html__( 'Enter the API version', 'super-forms' ) . ' (e.g: 2022-11-15)',
                        'placeholder' => 'YYYY-MM-DD',
                        'default' =>  '2022-11-15',
                        'filter' => true,
                        'parent' => 'stripe_mode',
                        'filter_value' => '',
                        'force_save' => true // if conditionally hidden, still store/save the value
                    ),
                    'stripe_live_webhook_id' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Live webhook ID', 'super-forms' ),
                        'label' => sprintf( esc_html__( '%sCreate a webhook ID%s%sEndpoint URL must be: %s', 'super-forms' ), '<a target="_blank" href="https://dashboard.stripe.com/webhooks">', '</a>', '<br /><br />', '<code>' . $webhookUrl . '</code>' ),
                        'desc' => sprintf( 
                            esc_html__( 'Make sure the following events are enabled for this webhook:%s%s%s%s%s', 'super-forms' ), 
                            '<br />',
                            '<code>checkout.session.async_payment_failed</code></br />',
                            '<code>checkout.session.async_payment_succeeded</code></br />',
                            '<code>checkout.session.completed</code></br />',
                            '<code>checkout.session.expired</code></br />'
                        ),
                        'placeholder' => 'we_XXXXXXXXXXXXXXXXXXXXXXXX',
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_mode',
                        'filter_value' => '',
                        'force_save' => true // if conditionally hidden, still store/save the value
                    ),
                    'stripe_live_webhook_secret' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Live webhook signing secret key', 'super-forms' ),
                        'desc' => '<a target="_blank" href="https://dashboard.stripe.com/webhooks">' . esc_html__( 'Get your webhook signing secret key', 'super-forms' ) . '</a>',
                        'placeholder' => 'whsec_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_mode',
                        'filter_value' => '',
                        'force_save' => true // if conditionally hidden, still store/save the value
                    ),
                )
            );
            return $array;
        }
// tmp
// tmp                    
// tmp                    'stripe_checkout' => array(
// tmp                        'hidden_setting' => true,
// tmp                        'default' =>  '',
// tmp                        'type' => 'checkbox',
// tmp                        'filter' => true,
// tmp                        'values' => array(
// tmp                            'true' => esc_html__( 'Enable Stripe Checkout', 'super-forms' ),
// tmp                        ),
// tmp                    ),
// tmp                    'stripe_receipt_email' => array(
// tmp                        'name' => esc_html__( 'Owner’s email address', 'super-forms' ),
// tmp                        'label' => esc_html__( '(optional)', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout_advanced',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp
// tmp					// @since 1.3.0 - Conditionally Stripe Checkout
// tmp					'conditionally_stripe_checkout' => array(
// tmp						'hidden_setting' => true,
// tmp						'default' =>  '',
// tmp						'type' => 'checkbox',
// tmp						'filter'=>true,
// tmp						'values' => array(
// tmp							'true' => esc_html__( 'Conditionally checkout to Stripe', 'super-forms' ),
// tmp						),
// tmp						'parent' => 'stripe_checkout',
// tmp						'filter_value' => 'true'
// tmp					),
// tmp					'conditionally_stripe_checkout_check' => array(
// tmp						'hidden_setting' => true,
// tmp						'type' => 'conditional_check',
// tmp						'name' => esc_html__( 'Only checkout to Stripe when following condition is met', 'super-forms' ),
// tmp						'label' => esc_html__( 'Your are allowed to enter field {tags} to do the check', 'super-forms' ),
// tmp						'default' =>  '',
// tmp						'placeholder' => "{fieldname},value",
// tmp						'filter'=>true,
// tmp						'parent' => 'conditionally_stripe_checkout',
// tmp						'filter_value' => 'true'
// tmp					),
// tmp                    'stripe_method' => array(
// tmp                        'name' => esc_html__( 'Stripe checkout method', 'super-forms' ),
// tmp                        'default' =>  'single',
// tmp                        'type' => 'select',
// tmp                        'values' => array(
// tmp                            'single' => esc_html__( 'Single product or service checkout', 'super-forms' ),
// tmp                            'subscription' => esc_html__( 'Subscription checkout', 'super-forms' )
// tmp                        ),
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp
// tmp                    'stripe_payment_methods' => array(
// tmp                        'name' => esc_html__( 'Payment method', 'super-forms' ),
// tmp                        'label' => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ) . '. ' . esc_html__( 'Accepted values are', 'super-forms' ) . ': <code>card</code> <code>acss_debit</code> <code>afterpay_clearpay</code> <code>alipay</code> <code>au_becs_debit</code> <code>bacs_debit</code> <code>bancontact</code> <code>boleto</code> <code>eps</code> <code>fpx</code> <code>giropay</code> <code>grabpay</code> <code>ideal</code> <code>klarna</code> <code>konbini</code> <code>oxxo</code> <code>p24</code> <code>paynow</code> <code>sepa_debit</code> <code>sofort</code> <code>us_bank_account</code> <code>wechat_pay</code>',
// tmp                        'default' =>  'card',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_method',
// tmp                        'filter_value' => 'single,subscription',
// tmp                    ),
// tmp                    'stripe_currency' => array(
// tmp                        'name' => esc_html__( 'Currency', 'super-forms' ),
// tmp                        'label' => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ) . '. ' . esc_html__( 'Three-letter ISO currency code e.g USD, CAD, EUR', 'super-forms' ),
// tmp                        'default' =>  'card',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_method',
// tmp                        'filter_value' => 'single,subscription',
// tmp                    ),
// tmp
// tmp                    // Subscription checkout settings
// tmp                    'stripe_plan_id' => array(
// tmp                        'name' => esc_html__( 'Subscription Plan ID (should look similar to: plan_G0FvDp6vZvdwRZ)', 'super-forms' ),
// tmp                        'label' => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ) . '. ' . sprintf( esc_html__( 'You can find your plan ID’s under %sBilling > Products > Pricing plans%s.', 'super-forms' ), '<a target="_blank" href="https://dashboard.stripe.com/subscriptions/products/">', '</a>' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_method',
// tmp                        'filter_value' => 'subscription',
// tmp                    ),
// tmp                    'stripe_billing_email' => array(
// tmp                        'name' => esc_html__( 'Billing E-mail address (required)', 'super-forms' ),
// tmp                        'label' => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_method',
// tmp                        'filter_value' => 'subscription',
// tmp                    ),
// tmp
// tmp
// tmp                    // Single checkout settings
// tmp                    'stripe_amount' => array(
// tmp                        'name' => esc_html__( 'Amount to charge', 'super-forms' ),
// tmp                        'label' => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_method',
// tmp                        'filter_value' => 'single',
// tmp                    ),
// tmp                    'stripe_description' => array(
// tmp                        'name' => esc_html__( 'Description', 'super-forms' ),
// tmp                        'label' => esc_html__( 'An arbitrary string which you can attach to a Charge object. It is displayed when in the web interface alongside the charge. Note that if you use Stripe to send automatic email receipts to your customers, your receipt emails will include the description of the charge(s) that they are describing.', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp                    'stripe_currency' => array(
// tmp                        'name' => esc_html__( 'Currency', 'super-forms' ),
// tmp                        'label' => sprintf( esc_html__( 'Three-letter ISO code for the currency e.g: USD, AUD, EUR. List of %ssupported currencies%s.', 'super-forms' ), '<a target="_blank" href="https://stripe.com/docs/currencies">', '</a>' ),
// tmp                        'default' =>  'usd',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp                    'stripe_return_url' => array(
// tmp                        'name' => esc_html__( 'Thank you page (return URL)', 'super-forms' ),
// tmp                        'label' => esc_html__( 'Return the customer to this page after a sucessfull payment. Leave blank to redirect to home page.', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp                   
// tmp                    'stripe_completed_entry_status' => array(
// tmp                        'name' => esc_html__( 'Entry status after payment completed', 'super-forms' ),
// tmp                        'label' => sprintf( esc_html__( 'You can add custom statuses via %sSuper Forms > Settings > Backend Settings%s if needed', 'super-forms' ), '<a target="blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#backend-settings') . '">', '</a>' ),
// tmp                        'default' =>  'completed',
// tmp                        'type' => 'select',
// tmp                        'values' => $statuses,
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp
// tmp                    // Advanced settings
// tmp                    'stripe_checkout_advanced' => array(
// tmp                        'hidden_setting' => true,
// tmp                        'default' =>  '',
// tmp                        'type' => 'checkbox',
// tmp                        'values' => array(
// tmp                            'true' => esc_html__( 'Show advanced settings', 'super-forms' ),
// tmp                        ),
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp                    'stripe_statement_descriptor' => array(
// tmp                        'name' => esc_html__( 'Statement descriptor', 'super-forms' ),
// tmp                        'label' => esc_html__( 'You can use this value as the complete description that appears on your customers statements. Must contain at least one letter, maximum 22 characters. An arbitrary string to be displayed on your customer’s statement. As an example, if your website is "RunClub" and the item you’re charging for is a race ticket, you may want to specify "RunClub 5K race ticket".', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout_advanced',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp
// tmp                    // Owner
// tmp                    'stripe_email' => array(
// tmp                        'name' => esc_html__( 'Owner’s email address', 'super-forms' ),
// tmp                        'label' => esc_html__( '(optional)', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout_advanced',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp                    'stripe_name' => array(
// tmp                        'name' => esc_html__( 'Owner’s full name', 'super-forms' ),
// tmp                        'label' => esc_html__( '(optional)', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout_advanced',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp                    'stripe_city' => array(
// tmp                        'name' => esc_html__( 'Owner’s City', 'super-forms' ),
// tmp                        'label' => esc_html__( '(optional)', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout_advanced',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp                    'stripe_country' => array(
// tmp                        'name' => esc_html__( 'Owner’s Country', 'super-forms' ),
// tmp                        'label' => esc_html__( '(optional)', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout_advanced',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp                    'stripe_line1' => array(
// tmp                        'name' => esc_html__( 'Owner’s Address line1', 'super-forms' ),
// tmp                        'label' => esc_html__( '(optional)', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout_advanced',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp                    'stripe_line2' => array(
// tmp                        'name' => esc_html__( 'Owner’s Address line 2', 'super-forms' ),
// tmp                        'label' => esc_html__( '(optional)', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout_advanced',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp                    'stripe_postal_code' => array(
// tmp                        'name' => esc_html__( 'Owner’s Postal code', 'super-forms' ),
// tmp                        'label' => esc_html__( '(optional)', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout_advanced',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp                    'stripe_state' => array(
// tmp                        'name' => esc_html__( 'Owner’s State', 'super-forms' ),
// tmp                        'label' => esc_html__( '(optional)', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout_advanced',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp                    'stripe_phone' => array(
// tmp                        'name' => esc_html__( 'Owner’s phone number', 'super-forms' ),
// tmp                        'label' => esc_html__( '(optional)', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout_advanced',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp
// tmp                    // Carrier
// tmp                    'stripe_carrier' => array(
// tmp                        'name' => esc_html__( 'Carrier (optional)', 'super-forms' ),
// tmp                        'label' => esc_html__( 'The delivery service that shipped a physical product, such as Fedex, UPS, USPS, etc.', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout_advanced',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp                    // Tracking number
// tmp                    'stripe_tracking_number' => array(
// tmp                        'name' => esc_html__( 'Tracking number (optional)', 'super-forms' ),
// tmp                        'label' => esc_html__( 'The tracking number for a physical product, obtained from the delivery service. If multiple tracking numbers were generated for this purchase, please separate them with commas.', 'super-forms' ),
// tmp                        'default' =>  '',
// tmp                        'filter' => true,
// tmp                        'parent' => 'stripe_checkout_advanced',
// tmp                        'filter_value' => 'true',
// tmp                    ),
// tmp
// tmp                )
// tmp            );
// tmp            if (class_exists('SUPER_Frontend_Posting')) {
// tmp                $array['stripe_checkout']['fields']['stripe_completed_post_status'] = array(
// tmp                    'name' => esc_html__( 'Post status after payment complete', 'super-forms' ),
// tmp                    'label' => esc_html__( 'Only used for Front-end posting', 'super-forms' ),
// tmp                    'default' =>  'publish',
// tmp                    'type' => 'select',
// tmp                    'values' => array(
// tmp                        'publish' => esc_html__( 'Publish (default)', 'super-forms' ),
// tmp                        'future' => esc_html__( 'Future', 'super-forms' ),
// tmp                        'draft' => esc_html__( 'Draft', 'super-forms' ),
// tmp                        'pending' => esc_html__( 'Pending', 'super-forms' ),
// tmp                        'private' => esc_html__( 'Private', 'super-forms' ),
// tmp                        'trash' => esc_html__( 'Trash', 'super-forms' ),
// tmp                        'auto-draft' => esc_html__( 'Auto-Draft', 'super-forms' ),
// tmp                    ),
// tmp                    'filter' => true,
// tmp                    'parent' => 'stripe_checkout',
// tmp                    'filter_value' => 'true',
// tmp                );
// tmp            }
// tmp            if (class_exists('SUPER_Register_Login')) {
// tmp                global $wp_roles;
// tmp                $all_roles = $wp_roles->roles;
// tmp                $editable_roles = apply_filters( 'editable_roles', $all_roles );
// tmp                $roles = array();
// tmp                foreach( $editable_roles as $k => $v ) {
// tmp                    $roles[$k] = $v['name'];
// tmp                }
// tmp                $array['stripe_checkout']['fields']['stripe_completed_signup_status'] = array(
// tmp                    'name' => esc_html__( 'Registered user login status after payment complete', 'super-forms' ),
// tmp                    'label' => esc_html__( 'Only used for Register & Login feature', 'super-forms' ),
// tmp                    'default' =>  'active',
// tmp                    'type' => 'select',
// tmp                    'values' => array(
// tmp                        'active' => esc_html__( 'Active (default)', 'super-forms' ),
// tmp                        'pending' => esc_html__( 'Pending', 'super-forms' ),
// tmp                        'blocked' => esc_html__( 'Blocked', 'super-forms' ),
// tmp                    ),
// tmp                    'filter' => true,
// tmp                    'parent' => 'stripe_checkout',
// tmp                    'filter_value' => 'true',
// tmp                );
// tmp				$array['stripe_checkout']['fields']['stripe_completed_user_role'] = array(
// tmp					'name' => esc_html__( 'Change user role after payment complete', 'super-forms' ),
// tmp					'label' => esc_html__( 'Only used for Register & Login feature', 'super-forms' ),
// tmp					'default' =>  '',
// tmp					'type' => 'select',
// tmp					'values' => array_merge($roles, array('' => esc_html__( 'Do not change role', 'super-forms' ))),
// tmp					'filter' => true,
// tmp					'parent' => 'stripe_checkout',
// tmp					'filter_value' => 'true',
// tmp				);
// tmp            }
// tmp            return $array;
// tmp        }

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
