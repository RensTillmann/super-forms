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
                add_action( 'manage_super_stripe_txn_posts_custom_column', array( $this, 'super_custom_columns' ), 10, 2 );
                add_action( 'manage_super_stripe_sub_posts_custom_column', array( $this, 'super_custom_columns' ), 10, 2 );

                add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
                add_filter( 'super_settings_after_smtp_server_filter', array( $this, 'add_settings' ), 10, 2 );
                add_action( 'init', array( $this, 'update_plugin' ) );
                add_action( 'all_admin_notices', array( $this, 'display_activation_msg' ) );

                add_action( 'current_screen', array( $this, 'after_screen' ), 0 );
                add_filter( 'post_row_actions', array( $this, 'remove_row_actions' ), 10, 1 );
                
                add_action( 'after_contact_entry_metabox_hook', array( $this, 'add_transaction_link' ), 0 );
            }

            if ( $this->is_request( 'ajax' ) ) {
                //add_action( 'super_before_sending_email_hook', array( $this, 'create_stripe_customer' ), 10, 1 );
            }

            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
            //add_filter( 'super_redirect_url_filter', array( $this, 'stripe_redirect' ), 10, 2 );
            add_action( 'super_front_end_posting_after_insert_post_action', array( $this, 'save_post_id' ) );
            add_action( 'super_after_wp_insert_user_action', array( $this, 'save_user_id' ) );
            add_action( 'super_stripe_webhook_payment_intent_succeeded', array( $this, 'payment_intent_succeeded' ), 10 );

            // Prepare payment
            add_action( 'wp_ajax_super_stripe_prepare_payment', array( $this, 'stripe_prepare_payment' ) );
            add_action( 'wp_ajax_nopriv_super_stripe_prepare_payment', array( $this, 'stripe_prepare_payment' ) );

            // Create customer
            add_action( 'wp_ajax_super_stripe_create_subscription', array( $this, 'stripe_create_subscription' ) );
            add_action( 'wp_ajax_nopriv_super_stripe_create_subscription', array( $this, 'stripe_create_subscription' ) );
            

            // Filters since 1.2.3
            if ( ( $this->is_request( 'frontend' ) ) || ( $this->is_request( 'admin' ) ) ) {
                add_filter( 'super_common_js_dynamic_functions_filter', array( $this, 'add_dynamic_function' ), 100, 2 );
            }

            add_filter( 'super_form_styles_filter', array( $this, 'add_stripe_styles' ), 100, 2 );

        }
        

        // public static function create_stripe_customer($atts) {
        //     $post = $atts['post'];
        //     $settings = $atts['settings'];
        //     // Set your secret key: remember to change this to your live secret key in production
        //     // See your keys here: https://dashboard.stripe.com/account/apikeys
        //     \Stripe\Stripe::setApiKey('sk_test_4eC39HqLyjWDarjtT1zdp7dc');
        //     \Stripe\Customer::create([
        //       'email' => 'jenny.rosen@example.com',
        //       'payment_method' => 'pm_1FWS6ZClCIKljWvsVCvkdyWg',
        //       'invoice_settings' => [
        //         'default_payment_method' => 'pm_1FWS6ZClCIKljWvsVCvkdyWg',
        //       ],
        //     ]);
        // }
        // public static function create_stripe_customer($atts) {
        //     $post = $atts['post'];
        //     $settings = $atts['settings'];
        // }



        /**
         * Add the Stripe transaction link to the entry info/data page
         *
         * @since       1.0.0
         */
        public static function add_transaction_link($entry_id) {
            $post_id = get_post_meta( $entry_id, '_super_stripe_txn_id', true );
            if(!empty($post_id)){
                $data = get_post_meta( $post_id, '_super_txn_data', true );
                $txn_id = substr($data['id'], 0, 15);
                ?>
                <div class="misc-pub-section">
                    <span><?php echo esc_html__('Stripe Transaction', 'super-forms' ).':'; ?> <strong><?php echo '<a target="_blank" href="https://dashboard.stripe.com/payments/' . $data['id'] . '">' . $txn_id . ' ...</a>'; ?></strong></span>
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
            require_once( 'stripe-php/init.php' );
            $data = $_POST['data'];
            $payment_method = $_POST['payment_method'];
            $form_id = absint($data['hidden_form_id']['value']);
            $settings = SUPER_Common::get_form_settings($form_id);

            // Set your secret key: remember to change this to your live secret key in production
            // See your keys here: https://dashboard.stripe.com/account/apikeys
            \Stripe\Stripe::setApiKey('sk_test_CczNHRNSYyr4TenhiCp7Oz05');

            try {
                $customer = \Stripe\Customer::create([
                    'email' => 'jenny.rosen@example.com',
                    'payment_method' => $payment_method,
                    'invoice_settings' => [
                        'default_payment_method' => $payment_method
                    ],
                ]);
            } catch(\Stripe\Error\Card $e) {
                // Since it's a decline, \Stripe\Error\Card will be caught
                $message = $e->getJsonBody()['error']['message'];
                echo json_encode( array( 'error' => array( 'message' => $message ) ) );
                die();
            } catch(\Stripe\Exception\CardException $e) {
                // Since it's a decline, \Stripe\Exception\CardException will be caught
                echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                die();
            } catch (\Stripe\Exception\RateLimitException $e) {
                // Too many requests made to the API too quickly
                echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                die();
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // Invalid parameters were supplied to Stripe's API
                echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                die();
            } catch (\Stripe\Exception\AuthenticationException $e) {
                // Authentication with Stripe's API failed
                // (maybe you changed API keys recently)
                echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                die();
            } catch (\Stripe\Exception\ApiConnectionException $e) {
                // Network communication with Stripe failed
                echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                die();
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // Display a very generic error to the user, and maybe send
                // yourself an email
                echo json_encode( array( 'error' => array( 'message' => esc_html__( 'An error occured with the Stripe API', 'super-forms' ) ) ) );
                die();
            } catch (Exception $e) {
                // Something else happened, completely unrelated to Stripe
                echo json_encode( array( 'error' => array( 'message' => esc_html__( 'An error occured', 'super-forms' ) ) ) );
                die();
            }

            try {
                // Attempt to create the subscriptions
                $outcome = \Stripe\Subscription::create([
                    'customer' => $customer->id,
                    'items' => [
                        [
                            'plan' => $settings['stripe_plan_id'],
                        ],
                    ],
                    // 'trial_period_days' => 0, // Integer representing the number of trial period days before the customer is charged for the first time. This will always overwrite any trials that might apply via a subscribed plan.
                    'payment_behavior' => 'allow_incomplete',
                    'expand' => ['latest_invoice.payment_intent']
                ]);
            } catch(\Stripe\Error\Card $e) {
                // Since it's a decline, \Stripe\Error\Card will be caught
                $message = $e->getJsonBody()['error']['message'];
                echo json_encode( array( 'error' => array( 'message' => $message ) ) );
                die();
            } catch(\Stripe\Exception\CardException $e) {
                // Since it's a decline, \Stripe\Exception\CardException will be caught
                echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                die();
            } catch (\Stripe\Exception\RateLimitException $e) {
                // Too many requests made to the API too quickly
                echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                die();
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // Invalid parameters were supplied to Stripe's API
                echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                die();
            } catch (\Stripe\Exception\AuthenticationException $e) {
                // Authentication with Stripe's API failed
                // (maybe you changed API keys recently)
                echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                die();
            } catch (\Stripe\Exception\ApiConnectionException $e) {
                // Network communication with Stripe failed
                echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                die();
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // Display a very generic error to the user, and maybe send
                // yourself an email
                echo json_encode( array( 'error' => array( 'message' => esc_html__( 'An error occured with the Stripe API', 'super-forms' ) ) ) );
                die();
            } catch (Exception $e) {
                // Something else happened, completely unrelated to Stripe
                echo json_encode( array( 'error' => array( 'message' => esc_html__( 'An error occured', 'super-forms' ) ) ) );
                die();
            }

            echo json_encode( array( 
                'client_secret' => $outcome->latest_invoice->payment_intent->client_secret,
                'subscription_status' => $outcome->status,
                'invoice_status' => $outcome->latest_invoice->status,
                'paymentintent_status' => (isset($outcome->latest_invoice->payment_intent) ? $outcome->latest_invoice->payment_intent->status : '')
            ) );

            // // Outcome 1: Payment succeeds
            // if( ($outcome->status=='active') && ($outcome->latest_invoice->status=='paid') && ($outcome->latest_invoice->payment_intent->status=='succeeded') ) {

            // }
            // // Outcome 2: Trial starts
            // if( ($outcome->status=='trialing') && ($outcome->latest_invoice->status=='paid') ) {

            // }
            // // Outcome 3: Payment fails
            // if( ($outcome->status=='incomplete') && ($outcome->latest_invoice->status=='open') && ($outcome->latest_invoice->payment_intent->status=='requires_payment_method') ) {

            // }

            // // Outcome 4: Requires action
            // if( ($outcome->status=='incomplete') && ($outcome->latest_invoice->status=='open') && ($outcome->latest_invoice->payment_intent->status=='requires_action') ) {

            // }

            die();
        }

        /**
         * Create Stripe Payment Intent
         *
         *  @since      1.0.0
         */
        public static function stripe_prepare_payment() {
            require_once( 'stripe-php/init.php' );
            // Get data from form
            $ideal = (bool) (isset($_POST['ideal']) ? true : false);
            $sepa_debit = (bool) (isset($_POST['sepa_debit']) ? true : false);
            $data = $_POST['data'];
            $form_id = absint($data['hidden_form_id']['value']);
            // Get form settings
            $settings = SUPER_Common::get_form_settings($form_id);

            if($settings['stripe_method']=='subscription'){
                
                if($ideal){
                    // Subscription
                    // Step 1: Collect customer & subscription information
                    // see data
                    // Step 2: Collect payment information
                    // we do this on javascript side with createPaymentMethod
                    // Step 3: Create the customer
                    // Set your secret key: remember to change this to your live secret key in production
                    // See your keys here: https://dashboard.stripe.com/account/apikeys
                    // \Stripe\Stripe::setApiKey('sk_test_CczNHRNSYyr4TenhiCp7Oz05');

                    // \Stripe\Customer::create([
                    //   'email' => 'jenny.rosen@example.com',
                    //   'payment_method' => 'pm_1FWS6ZClCIKljWvsVCvkdyWg',
                    //   'invoice_settings' => [
                    //     'default_payment_method' => 'pm_1FWS6ZClCIKljWvsVCvkdyWg',
                    //   ],
                    // ]);

                    // Set your secret key: remember to change this to your live secret key in production
                    // See your keys here: https://dashboard.stripe.com/account/apikeys
                    \Stripe\Stripe::setApiKey('sk_test_CczNHRNSYyr4TenhiCp7Oz05');

                    $amount = SUPER_Common::email_tags( $settings['stripe_amount'], $data, $settings );
                    $amount = SUPER_Common::tofloat($amount)*100;
                    if(empty($settings['stripe_return_url'])) $settings['stripe_return_url'] = get_home_url(); // default to home page
                    $stripe_return_url = esc_url(SUPER_Common::email_tags( $settings['stripe_return_url'], $data, $settings ));
                    
                    try {
                        $source = \Stripe\Source::create([
                            "type" => "ideal",
                            "amount" => $amount,
                            "currency" => "eur",
                            "owner" => [
                                "name" => "Jenny Rosen",
                            ],
                            "redirect" => [
                                "return_url" => $stripe_return_url,
                            ],             
                        ]);
                    } catch(\Stripe\Error\Card $e) {
                        // Since it's a decline, \Stripe\Error\Card will be caught
                        $message = $e->getJsonBody()['error']['message'];
                        echo json_encode( array( 'error' => array( 'message' => $message ) ) );
                        die();
                    } catch(\Stripe\Exception\CardException $e) {
                        // Since it's a decline, \Stripe\Exception\CardException will be caught
                        echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                        die();
                    } catch (\Stripe\Exception\RateLimitException $e) {
                        // Too many requests made to the API too quickly
                        echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                        die();
                    } catch (\Stripe\Exception\InvalidRequestException $e) {
                        // Invalid parameters were supplied to Stripe's API
                        echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                        die();
                    } catch (\Stripe\Exception\AuthenticationException $e) {
                        // Authentication with Stripe's API failed
                        // (maybe you changed API keys recently)
                        echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                        die();
                    } catch (\Stripe\Exception\ApiConnectionException $e) {
                        // Network communication with Stripe failed
                        echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                        die();
                    } catch (\Stripe\Exception\ApiErrorException $e) {
                        // Display a very generic error to the user
                        echo json_encode( array( 'error' => array( 'message' => esc_html__( 'An error occured with the Stripe API', 'super-forms' ) ) ) );
                        die();
                    } catch (Exception $e) {
                        // Something else happened, completely unrelated to Stripe
                        echo json_encode( array( 'error' => array( 'message' => esc_html__( 'An error occured', 'super-forms' ) ) ) );
                        die();
                    }
                    echo json_encode( array( 
                        'method' => 'subscription',
                        'ideal' => $ideal,
                        'sepa_debit' => $sepa_debit,
                        'source' => $source
                    ) );
                }
                die();

            }else{
                // Get PaymentIntent data
                $amount = SUPER_Common::email_tags( $settings['stripe_amount'], $data, $settings );
                $amount = SUPER_Common::tofloat($amount)*100;
                $currency = (!empty($settings['stripe_currency']) ? sanitize_text_field($settings['stripe_currency']) : 'usd');
                $description = (!empty($settings['stripe_description']) ? sanitize_text_field($settings['stripe_description']) : '');

                // Set meta data
                // A set of key-value pairs that you can attach to a source object. 
                // It can be useful for storing additional information about the source in a structured format.
                $metadata = array();
                $metadata['_super_form_id'] = $form_id;
                $metadata['_super_author_id'] = absint(get_current_user_id());

                // Get Post ID and save it in custom parameter for stripe so we can update the post status after successfull payment complete
                $post_id = SUPER_Forms()->session->get( '_super_stripe_frontend_post_id' );
                if( !empty($post_id) ) {
                    $metadata['_super_stripe_frontend_post_id'] = absint($post_id);
                }
                // Get User ID and save it in custom parameter for stripe so we can update the user status after successfull payment complete
                $user_id = SUPER_Forms()->session->get( '_super_stripe_frontend_user_id' );
                if( !empty($user_id) ) {
                    $metadata['_super_stripe_frontend_user_id'] = absint($user_id);
                }
                // Get Contact Entry ID and save it so we can update the entry status after successfull payment
                if(!empty($settings['save_contact_entry']) && $settings['save_contact_entry']=='yes'){
                    $response = $_POST['response'];
                    $contact_entry_id = 0;
                    if(!empty($response['response_data'])){
                        if(!empty($response['response_data']['contact_entry_id'])){
                            $contact_entry_id = absint($response['response_data']['contact_entry_id']);
                        }
                    }
                    $metadata['_super_contact_entry_id'] = $contact_entry_id;
                }

                // Allow devs to filter metadata if needed
                $metadata = apply_filters( 'super_stripe_prepare_payment_metadata', $metadata, array('settings'=>$settings, 'data'=>$data, 'ideal'=>$ideal) );

                // Set your secret key: remember to change this to your live secret key in production
                // See your keys here: https://dashboard.stripe.com/account/apikeys
                \Stripe\Stripe::setApiKey('sk_test_CczNHRNSYyr4TenhiCp7Oz05');
                try {
                    $intent = \Stripe\PaymentIntent::create([
                        'amount' => $amount, // The amount to charge times hundred (because amount is in cents)
                        'currency' => ($ideal===true ? 'eur' : $currency),
                        'description' => $description,
                        'payment_method_types' => ($ideal===true ? array('card', 'ideal') : array('card')),
                        'receipt_email' => 'feeling4design@gmail.com', // Email address that the receipt for the resulting payment will be sent to.
                        // Shipping information for this PaymentIntent.
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
                        'metadata' => $metadata
                    ]);
                } catch(\Stripe\Error\Card $e) {
                    // Since it's a decline, \Stripe\Error\Card will be caught
                    $message = $e->getJsonBody()['error']['message'];
                    echo json_encode( array( 'error' => array( 'message' => $message ) ) );
                    die();
                } catch(\Stripe\Exception\CardException $e) {
                    // Since it's a decline, \Stripe\Exception\CardException will be caught
                    echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                    die();
                } catch (\Stripe\Exception\RateLimitException $e) {
                    // Too many requests made to the API too quickly
                    echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                    die();
                } catch (\Stripe\Exception\InvalidRequestException $e) {
                    // Invalid parameters were supplied to Stripe's API
                    echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                    die();
                } catch (\Stripe\Exception\AuthenticationException $e) {
                    // Authentication with Stripe's API failed
                    // (maybe you changed API keys recently)
                    echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                    die();
                } catch (\Stripe\Exception\ApiConnectionException $e) {
                    // Network communication with Stripe failed
                    echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
                    die();
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    // Display a very generic error to the user
                    echo json_encode( array( 'error' => array( 'message' => esc_html__( 'An error occured with the Stripe API', 'super-forms' ) ) ) );
                    die();
                } catch (Exception $e) {
                    // Something else happened, completely unrelated to Stripe
                    echo json_encode( array( 'error' => array( 'message' => esc_html__( 'An error occured', 'super-forms' ) ) ) );
                    die();
                }


                // Return client secret and return URL (only required for iDeal payments)
                if(empty($settings['stripe_return_url'])) $settings['stripe_return_url'] = get_home_url(); // default to home page
                $stripe_return_url = esc_url(SUPER_Common::email_tags( $settings['stripe_return_url'], $data, $settings ));
                echo json_encode( array( 
                    'client_secret' => $intent->client_secret, 
                    'return_url' => $stripe_return_url,
                    'stripe_method' => $settings['stripe_method']
                ) );
            }

            die();
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
            wp_enqueue_script( 'stripe-v3', '//js.stripe.com/v3/', array(), SUPER_Stripe()->version, false );
            $handle = 'super-stripe';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'stripe.js', array( 'stripe-v3', 'jquery', 'super-common' ), SUPER_Stripe()->version, false );  
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
                        'fontFamily' => ( isset( $settings['font_global_family'] ) ? stripslashes($settings['font_global_family']) : '"Open Sans",sans-serif' ),
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
         * Adjust filter/search for transactions and subscriptions
         *
         * @param  string $current_screen
         * 
         * @since       1.0.0
        */
        public function after_screen( $current_screen ) {
            if( $current_screen->id=='edit-super_stripe_txn' ) {
                add_filter( 'get_edit_post_link', array( $this, 'edit_post_link' ), 99, 2 );
            } 
            if( $current_screen->id=='super-forms_page_super_create_form' ) {
                self::stripe_element_scripts();
            }
        }
        public function edit_post_link( $link, $post_id ) {
            if( get_post_type()==='super_stripe_txn' ) {
                $data = get_post_meta( get_the_ID(), '_super_txn_data', true );
                return 'https://dashboard.stripe.com/payments/' . $data['id'];
            }
            return $link;
        }


        /**
         * Save Post ID into session after inserting post with Front-end Posting Add-on
         * This way we can add it to the Stripe metadata and use it later to update the post status after payment is completed
         * array( 'post_id'=>$post_id, 'data'=>$data, 'atts'=>$atts )
         *
         *  @since      1.0.0
         */
        public function save_post_id($atts) {
            SUPER_Forms()->session->set( '_super_stripe_frontend_post_id', absint($atts['post_id']) );
        }


        /**
         * Save User ID into session after creating user Front-end Register & Login add-on
         * This way we can add it to the Stripe metadata and use it later to update the user status after payment is completed
         * array( 'user_id'=>$user_id, 'atts'=>$atts )
         *
         *  @since      1.0.0
         */
        public function save_user_id($atts) {
            SUPER_Forms()->session->set( '_super_stripe_frontend_user_id', absint($atts['user_id']) );
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
            $columns['stripe_txn_id'] = 'Transaction';
            $columns['stripe_receipt'] = 'Receipt';
            $columns['post_status'] = 'Status';
            $columns['stripe_amount'] = 'Amount';
            $columns['stripe_description'] = 'Description';
            $columns['stripe_contact_entry'] = 'Contact Entry';
            $columns['stripe_form_id'] = 'Based on Form';
            $columns['author'] = 'Author';
            $columns['date'] = 'Date';
            return $columns;
        }


        public static function super_custom_columns($column, $post_id) {

            $obj = get_post_meta( $post_id, '_super_txn_data', true );
            $currency_code = strtoupper($obj['currency']);
            $symbol = (isset(self::$currency_codes[$currency_code]) ? self::$currency_codes[$currency_code]['symbol'] : $currency_code);
            switch ($column) {
                case 'stripe_txn_id':
                    echo '<a target="_blank" href="https://dashboard.stripe.com/payments/' . $obj['id'] . '">'.$obj['id'].'</a>';
                    break;
                case 'stripe_receipt':
                    echo (isset($obj['charges']['data'][0]['receipt_url']) ? '<a target="_blank" href="'.esc_url_raw($obj['charges']['data'][0]['receipt_url']).'">View Receipt</a>' : '');
                    break;
                case 'post_status':
                    echo 'Status: ' . $post_id;
                    break;
                case 'stripe_amount':
                    echo $symbol . number_format_i18n($obj['amount']/100, 2) . ' ' . $currency_code;
                    break;
                case 'stripe_description':
                    echo (isset($obj['description']) ? esc_html($obj['description']) : '');
                    break;
                case 'stripe_contact_entry':
                    if( isset($obj['metadata']['_super_contact_entry_id']) ) {
                        $entry_id = absint($obj['metadata']['_super_contact_entry_id']);
                        echo '<a target="_blank" href="' . get_edit_post_link( $entry_id ) . '">' . get_the_title($entry_id) . '</a>';
                    }
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
            $metadata['_super_stripe_description'] = SUPER_Common::email_tags( $settings['stripe_description'], $data, $settings );

            // Get Post ID and save it in custom parameter for stripe so we can update the post status after successfull payment complete
            $post_id = SUPER_Forms()->session->get( '_super_stripe_frontend_post_id' );
            if( !empty($post_id) ) {
                $metadata['_super_stripe_frontend_post_id'] = absint($post_id);
            }
            // Get User ID and save it in custom parameter for stripe so we can update the user status after successfull payment complete
            $user_id = SUPER_Forms()->session->get( '_super_stripe_frontend_user_id' );
            if( !empty($user_id) ) {
                $metadata['_super_stripe_frontend_user_id'] = absint($user_id);
            }
            // Get Contact Entry ID and save it so we can update the entry status after successfull payment
            if(!empty($settings['save_contact_entry']) && $settings['save_contact_entry']=='yes'){
                $metadata['_super_contact_entry_id'] = absint($data['contact_entry_id']['value']);
            }

            // Allow devs to filter metadata if needed
            $metadata = apply_filters( 'super_stripe_source_metadata', $metadata, array('settings'=>$settings, 'data'=>$data ) );

            // Check if Stripe checkout is enabled
            if($settings['stripe_checkout']=='true'){

                // If subscription checkout
                if($settings['stripe_method']=='subscription'){

                }

                // If single payment checkout
                if($settings['stripe_method']=='single'){
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
                        if(empty($settings['stripe_return_url'])) $settings['stripe_return_url'] = get_home_url(); // default to home page
                        $stripe_return_url = esc_url(SUPER_Common::email_tags( $settings['stripe_return_url'], $data, $settings ));

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
                                        'statement_descriptor' => $stripe_statement_descriptor // NOT USED? Unclear from Stripe API documentation
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
                            SUPER_Common::output_message(
                                $error = true,
                                $msg = $error_message
                            );
                        } else {
                            $obj = json_decode($response['body']);
                            if( !empty($obj->error) ) {
                                SUPER_Common::output_message(
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
                            SUPER_Common::output_message(
                                $error = true,
                                $msg = sprintf( esc_html__( 'Stripe Sandbox API key not configured, please enter your API key under %sSuper Forms > Settings > Stripe Checkout%s', 'super-forms' ), '<a target="_blank" href="' . admin_url() . 'admin.php?page=super_settings#stripe-checkout">', '</a>' )
                            );
                        }
                        if( (empty($global_settings['stripe_mode'])) && (empty($global_settings['stripe_live_key'])) ) {
                            SUPER_Common::output_message(
                                $error = true,
                                $msg = sprintf( esc_html__( 'Stripe Live API key not configured, please enter your API key under %sSuper Forms > Settings > Stripe Checkout%s', 'super-forms' ), '<a target="_blank" href="' . admin_url() . 'admin.php?page=super_settings#stripe-checkout">', '</a>' )
                            );
                        }
                        // Check if iDeal element exists
                        if( !isset($data['stripe_ideal']) ) {
                            SUPER_Common::output_message(
                                $error = true,
                                $msg = sprintf( esc_html__( 'No element found named %sstripe_ideal%s. Please make sure you added the Stripe iDeal element and named it %sstripe_ideal%s.', 'super-forms' ), '<strong>', '</strong>', '<strong>', '</strong>' )
                            );
                        }else{
                            SUPER_Common::output_message(
                                $error = true,
                                $msg = esc_html__( 'Please choose a bank.', 'super-forms' )
                            );             
                        }
                    }
                }
            }
            return $redirect;
        }


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

            if ((isset($_GET['page'])) && ($_GET['page'] == 'super_stripe_ipn')) {
                error_log( "IPN 1", 0 );

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


                //$event = $event->__toArray(true);
                $payload = json_decode($payload, true);
                $obj = $payload['data']['object'];
                $metadata = $obj['metadata'];

                $stripe_description = (isset($metadata['_super_stripe_description']) ? sanitize_text_field($metadata['_super_stripe_description']) : '');
                unset($metadata['_super_stripe_description']);

                // Handle the event
                error_log( "IPN 2", 0 );
                error_log( "Do action: super_stripe_webhook_" . str_replace('.', '_', $event['type']), 0 );
                do_action( 'super_stripe_webhook_' . str_replace('.', '_', $payload['type']), $obj );

                if($payload['type']==='source.chargeable'){

                    // A Source object becomes chargeable after a customer has authenticated and verified a payment.   
                    // @Todo: Create a Charge.
                    // @Message: Your order was received and is awaiting payment confirmation.
                    
                    $charge = \Stripe\Charge::create([

                        // amount
                        // REQUIRED
                        // A positive integer representing how much to charge in the smallest currency unit (e.g., 100 cents to charge $1.00 or 100 to charge ¥100, a zero-decimal currency). The minimum amount is $0.50 US or equivalent in charge currency. The amount value supports up to eight digits (e.g., a value of 99999999 for a USD charge of $999,999.99).
                        'amount' => $obj['amount'], // e.g: 1099,

                        // currency
                        // REQUIRED
                        // Three-letter ISO currency code, in lowercase. Must be a supported currency.
                        'currency' => $obj['currency'], // e.g: 'eur',

                        // application_fee_amount
                        // optional
                        // A fee in cents that will be applied to the charge and transferred to the application owner’s Stripe account. The request must be made with an OAuth key or the Stripe-Account header in order to take an application fee. For more information, see the application fees documentation.
                        //'application_fee_amount' => 16*100,

                        // capture
                        // optional
                        // Whether to immediately capture the charge. Defaults to true. When false, the charge issues an authorization (or pre-authorization), and will need to be captured later. Uncaptured charges expire in seven days. For more information, see the authorizing charges and settling later documentation.

                        // customer
                        // optional
                        // The ID of an existing customer that will be charged in this request.

                        // description
                        // optional
                        // An arbitrary string which you can attach to a Charge object. It is displayed when in the web interface alongside the charge. Note that if you use Stripe to send automatic email receipts to your customers, your receipt emails will include the description of the charge(s) that they are describing. This can be unset by updating the value to null and then saving.
                        'description' => $stripe_description, // e.g: '1 year license for Super Forms',

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
                        'source' => $obj['id'], // e.g: 'src_18eYalAHEMiOZZp1l9ZTjSU0',

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
                        //error_log( "Stripe Charge Error: " . $charge->error->message, 0 );
                    }
                }else{
                    // Update order data
                    if(isset($metadata['_super_txn_id'])){
                        $post_id = absint($metadata['_super_txn_id']);
                        if($post_id!==0){
                            // Save all transaction data
                            update_post_meta( $post_id, '_super_txn_data', $payload );
                        }
                    }
                }
                http_response_code(200);
                die();
            }
        }



        /**
         * Stripe Payment Intent Succeeded
         * This is stripes way of letting us know that the payment was successfully completed
         *
         * @since       1.0.0
         */
        public static function payment_intent_succeeded($payload) {
            error_log( "payment_intent_succeeded()", 0 );
            error_log( "Stripe IPN Payload: " . json_encode($payload), 0 );
            // If a charge succeeded create a "Transaction"
            $post_title = $payload['id']; // e.g: py_1Fa0hyFKn7uROhgCM31lbzor
            $metadata = (isset($payload['metadata']) ? $payload['metadata'] : array());
            $form_id = absint($metadata['_super_form_id']);
            $settings = SUPER_Common::get_form_settings($form_id);
            $post_author = absint($metadata['_super_author_id']);
            $contact_entry_id = (isset($metadata['_super_contact_entry_id']) ? absint($metadata['_super_contact_entry_id']) : 0 );
            $frontend_post_id = (isset($metadata['_super_stripe_frontend_post_id']) ? absint($metadata['_super_stripe_frontend_post_id']) : 0 );
            $frontend_user_id = (isset($metadata['_super_stripe_frontend_user_id']) ? absint($metadata['_super_stripe_frontend_user_id']) : 0 );

            // Create transaction
            $post = array(
                'post_status' => 'publish',
                'post_type' => 'super_stripe_txn',
                'post_title' => $post_title,
                'post_parent' => absint($form_id),
                'post_author' => absint($post_author)
            );
            $post_id = wp_insert_post($post);

            // Update "New" transaction counter with 1
            $count = get_option( 'super_stripe_txn_count', 0 );
            update_option( 'super_stripe_txn_count', ($count+1) );

            // Connect transaction to contact entry if one was created
            if( !empty($contact_entry_id) ) {
                error_log( "contact_entry_id: ", 0 );
                update_post_meta( $contact_entry_id, '_super_stripe_txn_id', $post_id );
                // Update contact entry status after succesfull payment
                if( !empty($settings['stripe_completed_entry_status']) ) {
                    error_log( "Stripe update entry status: " . $contact_entry_id, 0 );
                    update_post_meta( $contact_entry_id, '_super_contact_entry_status', $settings['stripe_completed_entry_status'] );
                }
            }
            // Update post status after succesfull payment (only used for Front-end Posting add-on)
            if( !empty($frontend_post_id) ) {
                error_log( "frontend_post_id: ", 0 );
                if( (!empty($settings['stripe_completed_post_status'])) && (!empty($frontend_post_id)) ) {
                    error_log( "Stripe update frontend post: " . $frontend_post_id, 0 );
                    wp_update_post( 
                        array(
                            'ID' => $frontend_post_id,
                            'post_status' => $settings['stripe_completed_post_status']
                        )
                    );
                }
            }
            // Update user status after succesfull payment (only used for Front-end Register & Login add-on)
            if( !empty($frontend_user_id) ) {
                error_log( "frontend_user_id: ", 0 );
                if( (!empty($settings['register_login_action'])) && ($settings['register_login_action']=='register') && (!empty($frontend_user_id)) ) {
                    if( ($frontend_user_id!=0) && (!empty($settings['stripe_completed_signup_status'])) ) {
                        error_log( "Stripe update_user_meta: " . $frontend_user_id, 0 );

                        update_user_meta( $frontend_user_id, 'super_user_login_status', $settings['stripe_completed_signup_status'] );
                    }
                }
            }
            // Save all transaction data
            add_post_meta( $post_id, '_super_txn_data', $payload );
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
            // $array['form_elements']['shortcodes']['stripe'] = array(
            //     'callback' => 'SUPER_Stripe::stripe_cc',
            //     'name' => 'Credit card',
            //     'icon' => 'stripe;fab',
            //     'atts' => array(
            //         'general' => array(
            //             'name' => esc_html__( 'General', 'super-forms' ),
            //             'fields' => array(
            //                 'label' => $label,
            //                 'description'=>$description,
            //                 'tooltip' => $tooltip
            //             ),
            //         ),
            //         'icon' => array(
            //             'name' => esc_html__( 'Icon', 'super-forms' ),
            //             'fields' => array(
            //                 'icon_position' => $icon_position,
            //                 'icon_align' => $icon_align,
            //                 'icon' => SUPER_Shortcodes::icon($attributes,''),
            //             ),
            //         ),
            //         'conditional_logic' => $conditional_logic_array
            //     )
            // );
            // $array['form_elements']['shortcodes']['stripe_ideal'] = array(
            //     'callback' => 'SUPER_Stripe::stripe_ideal',
            //     'name' => 'Stripe iDeal',
            //     'icon' => 'stripe;fab',
            //     'atts' => array(
            //         'general' => array(
            //             'name' => esc_html__( 'General', 'super-forms' ),
            //             'fields' => array(
            //                 'label' => $label,
            //                 'description'=>$description,
            //                 'tooltip' => $tooltip
            //             ),
            //         ),
            //         'icon' => array(
            //             'name' => esc_html__( 'Icon', 'super-forms' ),
            //             'fields' => array(
            //                 'icon_position' => $icon_position,
            //                 'icon_align' => $icon_align,
            //                 'icon' => SUPER_Shortcodes::icon($attributes,''),
            //             ),
            //         ),
            //         'conditional_logic' => $conditional_logic_array
            //     )
            // );

            // $banks = array(
            //     'abn_amro' => 'ABN Amro',
            //     'asn_bank' => 'ASN Bank',
            //     'bunq' => 'bunq B.V.‎',
            //     'handelsbanken' => 'Handelsbanken',
            //     'ing' => 'ING Bank',
            //     'knab' => 'Knab',
            //     'moneyou' => 'Moneyou',
            //     'rabobank' => 'Rabobank',
            //     'regiobank' => 'RegioBank',
            //     'sns_bank' => 'SNS Bank',
            //     'triodos_bank' => 'Triodos Bank',
            //     'van_lanschot' => 'Van Lanschot'
            // );
            // $dropdown_items = array();
            // foreach($banks as $k => $v){
            //     $dropdown_items[] = array(
            //         'checked' => false,
            //         'label' => $v,
            //         'value' => $k
            //     );
            // }
            // $array['form_elements']['shortcodes']['stripe_ideal'] = array(
            //     'name' => esc_html__( 'iDeal', 'super-forms' ),
            //     'icon' => 'stripe;fab',
            //     'predefined' => array(
            //         array(
            //             'tag' => 'dropdown',
            //             'group' => 'form_elements',
            //             'data' => array(
            //                 'name' => esc_html__( 'stripe_ideal', 'super-forms' ),
            //                 'email' => esc_html__( 'Stripe iDeal:', 'super-forms' ),
            //                 'placeholder' => esc_html__( '- selecteer uw bank -', 'super-forms' ),
            //                 'icon' => 'caret-square-down;far',
            //                 'dropdown_items' => $dropdown_items,
            //                 'validation' => 'empty',
            //                 'error' => esc_html__( 'Selecteer uw bank!', 'super-forms' )
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
         * Handle the Stripe element output
         * Depending on the users choice, this can print a Card, iDeal or IBAN (for SEPA payments) element
         *
         *  @since      1.0.0
        */
        public static function stripe_element( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null ) {
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
            $result .= SUPER_Shortcodes::loop_conditions( $atts );
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
            wp_enqueue_script( 'stripe-v3', '//js.stripe.com/v3/', array(), SUPER_Stripe()->version, false ); 
            $handle = 'super-stripe';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'stripe.js', array( 'stripe-v3', 'jquery', 'super-common' ), SUPER_Stripe()->version, false );  
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
                        'fontFamily' => ( isset( $settings['font_global_family'] ) ? stripslashes($settings['font_global_family']) : '"Open Sans",sans-serif' ),
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
            // Enqueu required scripts
            wp_enqueue_script( 'stripe-v3', '//js.stripe.com/v3/', array(), SUPER_Stripe()->version, false ); 
            $handle = 'super-stripe';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'stripe.js', array( 'stripe-v3', 'jquery', 'super-common' ), SUPER_Stripe()->version, false );  
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
                        'fontFamily' => ( isset( $settings['font_global_family'] ) ? stripslashes($settings['font_global_family']) : '"Open Sans",sans-serif' ),
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
            $result .= SUPER_Shortcodes::loop_conditions( $atts );
            $result .= '</div>';
            return $result;

            //require_once( 'stripe-php/init.php' );
            // Set your secret key: remember to change this to your live secret key in production
            // See your keys here: https://dashboard.stripe.com/account/apikeys
            //\Stripe\Stripe::setApiKey('sk_test_CczNHRNSYyr4TenhiCp7Oz05');

            // $intent = \Stripe\PaymentIntent::create([
            //     'amount' => 1099,
            //     'currency' => 'eur',
            // ]);
            // $result = '<input id="cardholder-name" type="text">
            // <!-- placeholder for Elements -->
            // <div id="card-element"></div>
            // <button id="card-button" data-secret="'.$intent->client_secret.'">
            //   Submit Payment
            // </button>';



            // // Fallback check for older super form versions
            // if (method_exists('SUPER_Common','generate_array_default_element_settings')) {
            //     $defaults = SUPER_Common::generate_array_default_element_settings($shortcodes, 'form_elements', $tag);
            // }else{
            //     $defaults = array(
            //     );
            // }
            // $atts = wp_parse_args( $atts, $defaults );
            // // @since Super Forms 4.7.0 - translation
            // if (method_exists('SUPER_Shortcodes','merge_i18n')) {
            //     $atts = SUPER_Shortcodes::merge_i18n($atts, $i18n); 
            // }
            // // Enqueu required scripts
            // wp_enqueue_script( 'stripe-v3', '//js.stripe.com/v3/', array(), SUPER_Stripe()->version, false );  
            // $handle = 'super-stripe-cc';
            // $name = str_replace( '-', '_', $handle ) . '_i18n';
            // wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'scripts-cc.js', array( 'jquery', 'super-common' ), SUPER_Stripe()->version, false );  
            // $global_settings = SUPER_Common::get_global_settings();
            // if(empty($global_settings['stripe_pk'])){
            //     $global_settings['stripe_pk'] = 'pk_test_1i3UyFAuxbe3Po62oX1FV47U';
            // }
            // wp_localize_script(
            //     $handle,
            //     $name,
            //     array( 
            //         'stripe_pk' => $global_settings['stripe_pk']
            //     )
            // );
            // wp_enqueue_script( $handle );

            // $result = SUPER_Shortcodes::opening_tag( $tag, $atts );
            // $result .= SUPER_Shortcodes::opening_wrapper( $atts, $inner, $shortcodes, $settings );
            // $result .= "<style></style>";
            // $result .= '<div class="form-row">';
            // $result .= '<div class="super-stripe-cc-element">';
            // $result .= '</div>';
            // $result .= '<!-- Used to display form errors. -->';
            // $result .= '<div class="super-stripe-errors" role="alert"></div>';
            // $result .= '</div>';
            // $result .= SUPER_Shortcodes::common_attributes( $atts, $tag );
            // $result .= '</div>';
            // $result .= SUPER_Shortcodes::loop_conditions( $atts );
            // $result .= '</div>';
            // return $result;        
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
        }


        /**
         * Hook into JS filter and add the Stripe Token
         *
         *  @since      1.0.0
        */
        public static function add_dynamic_function( $functions ) {
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
           
            $statuses = SUPER_Settings::get_entry_statuses();
            $new_statuses = array();
            foreach($statuses as $k => $v) {
                $new_statuses[$k] = $v['name'];
            }
            $statuses = $new_statuses;
            unset($new_statuses);

            // Stripe Settings
            $array['stripe_checkout'] = array(        
                'name' => esc_html__( 'Stripe Checkout', 'super-forms' ),
                'label' => esc_html__( 'Stripe Checkout', 'super-forms' ),
                'html' => array( '<style>.super-settings .stripe-settings-html-notice {display:none;}</style>', '<p class="stripe-settings-html-notice">' . sprintf( esc_html__( 'Before filling out these settings we %shighly recommend%s you to read the %sdocumentation%s.', 'super-forms' ), '<strong>', '</strong>', '<a target="_blank" href="https://renstillmann.github.io/super-forms/#/stripe-add-on">', '</a>' ) . '</p>' ),
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
                    

                    'stripe_checkout' => array(
                        'hidden_setting' => true,
                        'default' => SUPER_Settings::get_value(0, 'stripe_checkout', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'filter' => true,
                        'values' => array(
                            'true' => esc_html__( 'Enable Stripe Checkout', 'super-forms' ),
                        ),
                    ),
                    'stripe_method' => array(
                        'name' => esc_html__( 'Stripe checkout method', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_method', $settings['settings'], 'single' ),
                        'type' => 'select',
                        'values' => array(
                            'single' => esc_html__( 'Single product or service checkout', 'super-forms' ),
                            'subscription' => esc_html__( 'Subscription checkout', 'super-forms' )
                        ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),

                    // Subscription checkout settings
                    'stripe_plan_id' => array(
                        'name' => esc_html__( 'Subscription Plan/Product ID (should look similar to: plan_G0FvDp6vZvdwRZ)', 'super-forms' ),
                        'label' => esc_html__( 'You are allowed to use {tags}', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_plan_id', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_method',
                        'filter_value' => 'subscription',
                    ),
                    'stripe_billing_email' => array(
                        'name' => esc_html__( 'Billing E-mail address (required)', 'super-forms' ),
                        'label' => esc_html__( 'You are allowed to use {tags}', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_billing_email', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_method',
                        'filter_value' => 'subscription',
                    ),


                    // Single checkout settings
                    'stripe_amount' => array(
                        'name' => esc_html__( 'Amount to charge', 'super-forms' ),
                        'label' => esc_html__( 'You are allowed to use {tags}', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_amount', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_method',
                        'filter_value' => 'single',
                    ),
                    'stripe_description' => array(
                        'name' => esc_html__( 'Description', 'super-forms' ),
                        'label' => esc_html__( 'An arbitrary string which you can attach to a Charge object. It is displayed when in the web interface alongside the charge. Note that if you use Stripe to send automatic email receipts to your customers, your receipt emails will include the description of the charge(s) that they are describing.', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_description', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                    'stripe_currency' => array(
                        'name' => esc_html__( 'Currency', 'super-forms' ),
                        'label' => sprintf( esc_html__( 'Three-letter ISO code for the currency e.g: USD, AUD, EUR. List of %ssupported currencies%s.', 'super-forms' ), '<a target="_blank" href="https://stripe.com/docs/currencies">', '</a>' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_currency', $settings['settings'], 'USD' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                    'stripe_return_url' => array(
                        'name' => esc_html__( 'Thank you page (return URL)', 'super-forms' ),
                        'label' => esc_html__( 'Return the customer to this page after a sucessfull payment. Leave blank to redirect to home page.', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_return_url', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                   
                    'stripe_completed_entry_status' => array(
                        'name' => esc_html__( 'Entry status after payment completed', 'super-forms' ),
                        'label' => sprintf( esc_html__( 'You can add custom statuses via %sSuper Forms > Settings > Backend Settings%s if needed', 'super-forms' ), '<a target="blank" href="' . admin_url() . 'admin.php?page=super_settings#backend-settings">', '</a>' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_completed_entry_status', $settings['settings'], 'completed' ),
                        'type' => 'select',
                        'values' => $statuses,
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),

                    // Advanced settings
                    'stripe_checkout_advanced' => array(
                        'hidden_setting' => true,
                        'default' => SUPER_Settings::get_value(0, 'stripe_checkout_advanced', $settings['settings'], '' ),
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
                        'label' => esc_html__( 'You can use this value as the complete description that appears on your customers statements. Must contain at least one letter, maximum 22 characters. An arbitrary string to be displayed on your customer\'s statement. As an example, if your website is "RunClub" and the item you\'re charging for is a race ticket, you may want to specify "RunClub 5K race ticket".', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_statement_descriptor', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),


                    // Owner
                    'stripe_email' => array(
                        'name' => esc_html__( 'Owner\'s email address', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_email', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    'stripe_name' => array(
                        'name' => esc_html__( 'Owner\'s full name', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_name', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    'stripe_city' => array(
                        'name' => esc_html__( 'Owner\'s City', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_city', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    'stripe_country' => array(
                        'name' => esc_html__( 'Owner\'s Country', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_country', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    'stripe_line1' => array(
                        'name' => esc_html__( 'Owner\'s Address line1', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_line1', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    'stripe_line2' => array(
                        'name' => esc_html__( 'Owner\'s Address line 2', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_line2', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    'stripe_postal_code' => array(
                        'name' => esc_html__( 'Owner\'s Postal code', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_postal_code', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    'stripe_state' => array(
                        'name' => esc_html__( 'Owner\'s State', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_state', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    'stripe_phone' => array(
                        'name' => esc_html__( 'Owner\'s phone number', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_phone', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                )
            );
            if (class_exists('SUPER_Frontend_Posting')) {
                $array['stripe_checkout']['fields']['stripe_completed_post_status'] = array(
                    'name' => esc_html__( 'Post status after payment complete', 'super-forms' ),
                    'desc' => esc_html__( 'Only used for Front-end posting (publish, future, draft, pending, private, trash, auto-draft)', 'super-forms' ),
                    'default' => SUPER_Settings::get_value(0, 'stripe_completed_post_status', $settings['settings'], 'publish' ),
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
                $array['stripe_checkout']['fields']['stripe_completed_signup_status'] = array(
                    'name' => esc_html__( 'Registered user login status after payment complete', 'super-forms' ),
                    'desc' => esc_html__( 'Only used for Register & Login add-on (active, pending, blocked)', 'super-forms' ),
                    'default' => SUPER_Settings::get_value(0, 'stripe_completed_signup_status', $settings['settings'], 'active' ),
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
