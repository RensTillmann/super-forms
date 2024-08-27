<?php
if(!defined('ABSPATH')){
    exit; // Exit if accessed directly
}

if(!class_exists('SUPER_Stripe')) :

    final class SUPER_Stripe {
    
        public $add_on_slug = 'stripe';
        public $add_on_name = 'Stripe';
        public static $required_events = array(
            'checkout.session.async_payment_failed',
            'checkout.session.async_payment_succeeded',
            'checkout.session.completed',
            'checkout.session.expired',
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'invoice.paid',
            'invoice.payment_action_required',
            'invoice.payment_failed',
            'payment_intent.succeeded',
            'payment_intent.payment_failed'
        );
        protected static $_instance = null;
        public static function instance() {
            if(is_null( self::$_instance)){
                self::$_instance = new self();
            }
            return self::$_instance;
        }
        public function __construct(){
            $this->includes();
            $this->init_hooks();
            do_action('super_stripe_loaded');
        }
        public function includes(){
        }
        private function define($name, $value){
            if(!defined($name)){
                define($name, $value);
            }
        }
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
        private function init_hooks(){
            add_action( 'super_before_redirect_action', array( $this, 'redirect_to_stripe_checkout' ) );      
            if($this->is_request('admin')){
                add_filter( 'super_create_form_tabs', array( $this, 'add_tab' ), 10, 1 );
                add_action( 'super_create_form_stripe_tab', array( $this, 'add_tab_content' ) );
                add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
                add_action( 'after_contact_entry_metabox_hook', array( $this, 'add_transaction_link' ), 0 );
            }
        }
        public static function handle_webhooks($wp){
            if ( array_key_exists( 'sfssidr', $wp->query_vars ) ) {
                error_log('Re-create a checkout session based on the retry URL {stripe_retry_payment_url} inside E-mails');
                // This is used by the Stripe checkout.session.async_payment_failed
                try{
                    $api = self::setAppInfo();
                }catch(Exception $e){
                    self::exceptionHandler($e);
                }
                $s = \Stripe\Checkout\Session::retrieve($wp->query_vars['sfssidr'], []);
                $sfsi = get_option( '_sfsi_' . $s['metadata']['sfsi_id'], array() );
                $form_id = $sfsi['form_id'];
                $settings = SUPER_Common::get_form_settings($form_id);
                $s = self::get_default_stripe_settings($settings);
                $expiry = $s['retryPaymentEmail']['expiry']; // expiry in hours 1 = 1 hour, 0.5 = 30min.
                // Calculate expiry for the retry checkout session
                $expires_at = current_time('timestamp') + (3600 * $expiry);
                $sfsi['stripeData']['expires_at'] = $expires_at;
                $checkout_session = \Stripe\Checkout\Session::create($sfsi['stripeData']);
                wp_redirect($checkout_session->url);
                exit;
            }
            if ( array_key_exists( 'sfssids', $wp->query_vars ) ) {
                // Success URL
                error_log('Returned from Stripe Checkout session via success URL');
                // Do things
                try{
                    $api = self::setAppInfo();
                }catch(Exception $e){
                    self::exceptionHandler($e);
                }
                $s = \Stripe\Checkout\Session::retrieve($wp->query_vars['sfssids'], []);
                $m = $s['metadata'];
                //$sfsi = get_option( '_sfsi_' . $m['sfsi_id'], array() );
                // Now redirect to success URL without checkout session ID parameter
                $url = SUPER_Common::getClientData('stripe_home_success_url_'.$m['sf_id']);
                if($url===false){
                    wp_redirect(home_url());
                    exit;
                }
                // Check if the URL starts with http or https
                if(!preg_match('/^https?:\/\//', $url)){
                    // If not, prepend http:// to the URL
                    $url = 'http://' . $url;
                }
                wp_redirect(remove_query_arg(array('sfssid'), $url));
                exit;
            }
            if ( array_key_exists( 'sfssidc', $wp->query_vars ) ) {
                // Cancel URL
                error_log('Returned from Stripe Checkout session via cancel URL');
                // Do things
                try{
                    $api = self::setAppInfo();
                }catch(Exception $e){
                    self::exceptionHandler($e);
                }
                $s = \Stripe\Checkout\Session::retrieve($wp->query_vars['sfssidc'], []);
                $m = $s['metadata'];
                // Get form submission info
                $sfsi = get_option( '_sfsi_' . $m['sfsi_id'], array() );
                SUPER_Common::cleanupFormSubmissionInfo($m['sfsi_id'], 'stripe'); // stored in `wp_options` table as sfsi_%
                // Now redirect to cancel URL without checkout session ID parameter
                $sfsi['stripe_home_cancel_url'] = remove_query_arg(array('sfssid'), $sfsi['stripe_home_cancel_url'] );
                $sfsi['stripe_home_cancel_url'] = remove_query_arg(array('sfr'), $sfsi['stripe_home_cancel_url'] );
                if($sfsi){
                    if(!empty($sfsi['referer'])){
                        $url = add_query_arg('sfr', $m['sfsi_id'], $sfsi['referer']);
                        wp_redirect($url);
                        exit;
                    }else{
                        wp_redirect(home_url());
                        exit;
                    }
                }
                // Redirect to home URL instead
                error_log('Redirect to home URL instead');
                wp_redirect(add_query_arg('sfr', $m['sfsi_id'], $sfsi['stripe_home_cancel_url']));
                exit;
            }
            if ( array_key_exists( 'sfstripewebhook', $wp->query_vars ) ) {
                if($wp->query_vars['sfstripewebhook']==='true'){
                    // Success URL
                    // Set your secret key. Remember to switch to your live secret key in production.
                    // See your keys here: https://dashboard.stripe.com/apikeys
                    try{
                        $api = self::setAppInfo();
                    }catch(Exception $e){
                        self::exceptionHandler($e);
                    }
                    // You can find your endpoint's secret in your webhook settings
                    $global_settings = $api['global_settings'];
                    $endpoint_secret = $global_settings['stripe_' . $api['stripe_mode'] . '_webhook_secret']; // e.g: whsec_XXXXXXX
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
                    //error_log('Event object: ' . $event->data->object);
                    switch ($event->type) {
                        // Checkout session expired
                        case 'checkout.session.expired':
                            $stripe_session = $event->data->object;
                            error_log('sfsi_id: '.$stripe_session->metadata->sfsi_id);
                            SUPER_Common::cleanupFormSubmissionInfo($stripe_session->metadata->sfsi_id, $event->type);
                            break;
                        case 'checkout.session.completed':
                            // The customer has successfully authorized the debit payment by submitting the Checkout form. Wait for the payment to succeed or fail.
                            $stripe_session = $event->data->object;
                            SUPER_Common::triggerEvent('stripe.checkout.session.completed', array('sfsi_id'=>$stripe_session->metadata->sfsi_id));
                            if($stripe_session->payment_status=='paid' || $stripe_session->payment_status=='no_payment_required'){
                                // Update user role, post status, user login status or entry status
                                self::fulfillOrder(array('sfsi_id'=>$stripe_session->metadata->sfsi_id, 'stripe_session'=>$stripe_session)); //array('form_id'=>$form_id, 's'=>$s, 'stripe_session'=>$stripe_session, 'sfsi'=>$sfsi));
                                // moved into above function // Delete submission info
                                // moved into above function delete_option( '_sfsi_' . $stripe_session->metadata->sfsi_id );
                            }
                            // tmp $sfsi = get_option( '_sfsi_' . $stripe_session->metadata->sfsi_id, array() );
                            // tmp if(!isset($sfsi['form_id'])){
                            // tmp     error_log('Stripe: could not get form_id from submission info, probably because this info was expired');
                            // tmp     break;
                            // tmp }
                            // tmp $form_id = $sfsi['form_id'];
                            // tmp // Get form settings
                            // tmp $settings = SUPER_Common::get_form_settings($form_id);
                            // tmp $s = self::get_default_stripe_settings($settings);
                            // SUPER_Common::triggerEvent('stripe.checkout.session.completed', array('sfsi_id'=>$stripe_session->metadata->sfsi_id));
                                // tmp //'form_id'=>$form_id, 's'=>$s, 'stripe_session'=>$stripe_session, 'sfsi'=>$sfsi
                                // tmp 'i18n'=>$i18n, 
                                // tmp 'sfsi_id'=>$sfsi_id, 
                                // tmp 'post'=>$_POST, 
                                // tmp 'data'=>$data, 
                                // tmp 'settings'=>$settings, 
                                // tmp 'entry_id'=>$contact_entry_id, 
                                // tmp 'attachments'=>$attachments,
                                // tmp 'form_id'=>$form_id
                            // Check if the order is paid (for example, from a card payment)
                            // A delayed notification payment will have an `unpaid` status, as you're still waiting for funds to be transferred from the customer's account.
                            // tmp if($stripe_session->payment_status=='paid' || $stripe_session->payment_status=='no_payment_required'){
                            // tmp     // Update user role, post status, user login status or entry status
                            // tmp     self::fulfillOrder(array('form_id'=>$form_id, 's'=>$s, 'stripe_session'=>$stripe_session, 'sfsi'=>$sfsi));
                            // tmp     // Delete submission info
                            // tmp     delete_option( '_sfsi_' . $stripe_session->metadata->sfsi_id );
                            // tmp }
                            break;
                        case 'checkout.session.async_payment_succeeded':
                            // This step is only required if you plan to use any of the following payment methods:
                            // Bacs Direct Debit, Boleto, Canadian pre-authorized debits, Konbini, OXXO, SEPA Direct Debit, SOFORT, or ACH Direct Debit.
                            // Timings: bacs-debit T+6 (7 days max)
                            // Timings: ...
                            $stripe_session = $event->data->object;
                            SUPER_Common::triggerEvent('stripe.checkout.session.async_payment_succeeded', array('sfsi_id'=>$stripe_session->metadata->sfsi_id)); //array('form_id'=>$form_id, 's'=>$s, 'stripe_session'=>$stripe_session, 'sfsi'=>$sfsi));
                            self::fulfillOrder(array('sfsi_id'=>$stripe_session->metadata->sfsi_id, 'stripe_session'=>$stripe_session)); //array('form_id'=>$form_id, 's'=>$s, 'stripe_session'=>$stripe_session, 'sfsi'=>$sfsi));
                            //$sfsi = get_option( '_sfsi_' . $stripe_session->metadata->sfsi_id, array() );
                            //$form_id = $sfsi['form_id'];
                            // Get form settings
                            //$settings = SUPER_Common::get_form_settings($form_id);
                            //$s = self::get_default_stripe_settings($settings);
                            // Update user role, post status, user login status or entry status
                            // moved into above function // Delete submission info
                            // moved into above function delete_option( '_sfsi_' . $stripe_session->metadata->sfsi_id );
                            break;
                        case 'checkout.session.async_payment_failed':
                            // The payment was declined, or failed for some other reason. Contact the customer via email and request that they place a new order.
                            $stripe_session = $event->data->object;
                            SUPER_Common::triggerEvent('stripe.checkout.session.async_payment_failed', array('sfsi_id'=>$stripe_session->metadata->sfsi_id)); // array('form_id'=>$form_id, 's'=>$s, 'stripe_session'=>$stripe_session, 'sfsi'=>$sfsi));
                            self::paymentFailed(array('sfsi_id'=>$stripe_session->metadata->sfsi_id, 'stripe_session'=>$stripe_session)); //array('form_id'=>$form_id, 's'=>$s, 'stripe_session'=>$stripe_session, 'sfsi'=>$sfsi));
                            //$sfsi = get_option( '_sfsi_' . $stripe_session->metadata->sfsi_id, array() );
                            //$form_id = $sfsi['form_id'];
                            // tmp $to = $stripe_session->customer_details->email;
                            // tmp $data = $sfsi['data'];
                            // tmp // Get form settings
                            // tmp $settings = SUPER_Common::get_form_settings($form_id);
                            // tmp $s = self::get_default_stripe_settings($settings);
                            // tmp // Send an email to the customer asking them to retry their order
                            // tmp // @TODO via Triggers
                            // tmp $subject = SUPER_Common::email_tags( $s['retryPaymentEmail']['subject'], $data, $settings ); // e.g: Payment failed
                            // tmp $body = SUPER_Common::email_tags( $s['retryPaymentEmail']['body'], $data, $settings ); // e.g: 'Payment failed, please retry via the below URL:<br /><br /><a href="' . $retryUrl . '">' . $retryUrl . '</a>';
                            // tmp // Replace tag {stripe_retry_payment_expiry} with expiry (amount is in hours e.g: 48)
                            // tmp $expiry = SUPER_Common::email_tags( $s['retryPaymentEmail']['expiry'], $data, $settings ); // e.g: 'Payment failed, please retry via the below URL:<br /><br /><a href="' . $retryUrl . '">' . $retryUrl . '</a>';
                            // tmp $body = str_replace( '{stripe_retry_payment_expiry}', $expiry, $body );
                            // tmp // Replace tag {stripe_retry_payment_url} with URL
                            // tmp $domain = home_url(); // e.g: 'http://domain.com';
                            // tmp $home_url = trailingslashit($domain);
                            // tmp $retryUrl = $home_url . 'sfssid/retry/' . $stripe_session->id; //{CHECKOUT_SESSION_ID}';
                            // tmp $body = str_replace( '{stripe_retry_payment_url}', $retryUrl, $body );
                            // tmp if($s['retryPaymentEmail']['lineBreaks']==='true'){
                            // tmp     $body = nl2br($body);
                            // tmp }
                            // tmp $mail = SUPER_Common::email( array( 'to'=>$to, 'subject'=>$subject, 'body'=>$body ));
                            // tmp if($mail==false){
                            // tmp     http_response_code(400);
                            // tmp }
                            break;
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
                            self::afterSubscriptionUpdated($event);
                            break;
                        case 'customer.subscription.deleted':
                            // Sent when a customers subscription ends ("status": "canceled")
                            // This will also be the case for async payment failed (for delayed payment notifications like Sofort payments) 
                            error_log('Subscription was deleted');
                            self::afterSubscriptionDeleted($event);
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

                        // tmp case 'invoice.created':
                        // tmp     // Attach to entry?
                        // tmp     $invoice = $event->data->object;
                        // tmp     if(!empty($invoice->metadata->sf_entry)){
                        // tmp         $entry_id = absint($invoice->metadata->sf_entry);
                        // tmp         $stripe_connections = get_post_meta($entry_id, '_super_stripe_connections', true);
                        // tmp         if(!is_array($stripe_connections)) $stripe_connections = array();
                        // tmp         $stripe_connections['invoice'] = $invoice->id;
                        // tmp         update_post_meta($entry_id, '_super_stripe_connections', $stripe_connections);
                        // tmp     }
                        // tmp     // we don't want to store invoices on a server really... $invoice = $event->data->object;
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
                        // tmp     // we don't want to store invoices on a server really... // Use the WordPress HTTP API to download the file
                        // tmp     // we don't want to store invoices on a server really... $response = wp_remote_get($url);
                        // tmp     // we don't want to store invoices on a server really... if(is_wp_error($response)){
                        // tmp     // we don't want to store invoices on a server really...     $error_message = $response->get_error_message();
                        // tmp     // we don't want to store invoices on a server really... } 
                        // tmp     // we don't want to store invoices on a server really... if(!is_wp_error($response) && wp_remote_retrieve_response_code($response)===200){
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

                        // tmp     // we don't want to store invoices on a server really...     unset($GLOBALS['super_upload_dir']);
                        // tmp     // we don't want to store invoices on a server really...     add_filter( 'upload_dir', array( 'SUPER_Forms', 'filter_upload_dir' ));
                        // tmp     // we don't want to store invoices on a server really...     if(empty($GLOBALS['super_upload_dir'])){
                        // tmp     // we don't want to store invoices on a server really...         // upload directory is altered by filter: SUPER_Forms::filter_upload_dir()
                        // tmp     // we don't want to store invoices on a server really...         $GLOBALS['super_upload_dir'] = wp_upload_dir();
                        // tmp     // we don't want to store invoices on a server really...     }
                        // tmp     // we don't want to store invoices on a server really...     $d = $GLOBALS['super_upload_dir'];
                        // tmp     // we don't want to store invoices on a server really...     $is_secure_dir = substr($d['subdir'], 0, 3);
                        // tmp     // we don't want to store invoices on a server really...     $uploaded_file = wp_upload_bits($file_name, null, $body, $d['subdir']);
                        // tmp     // we don't want to store invoices on a server really...     if(!$uploaded_file['error']){
                        // tmp     // we don't want to store invoices on a server really...         $entry_id = $invoice->metadata->sf_entry;
                        // tmp     // we don't want to store invoices on a server really...         $stripe_connections = get_post_meta($entry_id, '_super_stripe_connections', array());
                        // tmp     // we don't want to store invoices on a server really...         // File uploaded successfully
                        // tmp     // we don't want to store invoices on a server really...         $filename = $uploaded_file['file'];
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
                        // tmp     // we don't want to store invoices on a server really...     }else{
                        // tmp     // we don't want to store invoices on a server really...         // File was not uploaded
                        // tmp     // we don't want to store invoices on a server really...     }
                        // tmp     // we don't want to store invoices on a server really... }
                        // tmp     break;
                    }
                    http_response_code(200);
                    exit;
                }
            }
        }

        public static function afterSubscriptionDeleted($event){
            self::stripeEventCustomerSubscriptionUpdatedOrDeleted($event);
        }
        public static function afterSubscriptionUpdated($event){
            self::stripeEventCustomerSubscriptionUpdatedOrDeleted($event);
        }
        public static function stripeEventCustomerSubscriptionUpdatedOrDeleted($event){
            $subscription = $event->data->object;
            $m = $subscription->metadata;
            $sfsi = get_option( '_sfsi_' . $m['sfsi_id'], array() );
            $data = $sfsi['data'];
            // Get form settings
            $settings = SUPER_Common::get_form_settings($m->sf_id);
            $s = self::get_default_stripe_settings($settings);
            if(!empty($m['sf_entry'])){
                // Maybe update entry status?
                error_log('Update entry status');
                foreach($s['subscription']['entry_status'] as $k => $v){
                    error_log($v['status']);
                    error_log($subscription->status);
                    if($v['status']===$subscription->status){
                        error_log('match');
                        $value = trim($v['value']);
                        if($value==='') continue; // do not change if empty
                        $value = SUPER_Common::email_tags($value, $data, $settings);
                        update_post_meta($m['sf_entry'], '_super_contact_entry_status', $value);
                        error_log('Updated entry status to '.$value.' for #'.$m['sf_entry']);
                        break;
                    }
                }
            }
            if(!empty($m['sf_post'])){
                // Maybe update post status?
                error_log('Update post status');
                foreach($s['subscription']['post_status'] as $k => $v){
                    if($v['status']===$subscription->status){
                        $value = trim($v['value']);
                        if($value==='') continue; // do not change if empty
                        $value = SUPER_Common::email_tags($value, $data, $settings);
                        wp_update_post(array('ID'=>$m['sf_post'], 'post_status'=>$value));
                        error_log('Updated post status to '.$value.' for #'.$m['sf_post']);
                        break;
                    }
                }
            }
            // Never update user role or login status for Administrator accounts to prevent locking out themselves
            if(!empty($m['sf_user']) && !user_can($m['sf_user'], 'administrator')){
                // Maybe update user role or user login status?
                error_log('Update user role or user login status');
                // Update user login status
                foreach($s['subscription']['login_status'] as $k => $v){
                    if($v['status']===$subscription->status){
                        $value = trim($v['value']);
                        if($value==='') continue; // do not change if empty
                        $value = SUPER_Common::email_tags($value, $data, $settings);
                        update_user_meta($m['sf_user'], 'super_user_login_status', $value);
                        error_log('Updated login status to '.$value.' for #'.$m['sf_user']);
                        break;
                    }
                }
                foreach($s['subscription']['user_role'] as $k => $v){
                    if($v['status']===$subscription->status){
                        $value = trim($v['value']);
                        if($value==='') continue; // do not change if empty
                        $value = SUPER_Common::email_tags($value, $data, $settings);
                        $userdata = array('ID'=>$m['sf_user'], 'role'=>$value);
                        $result = wp_update_user($userdata);
                        if(is_wp_error($result)){
                            error_log('Super Forms [ERROR]: failed to update user role to '.$value.' for user with ID: '.$m['sf_user'].' after Stripe subscription status changed');
                            error_log($result->get_error_message());
                            throw new Exception($result->get_error_message());
                        }
                        error_log('Updated user role to '.$value.' for #'.$m['sf_user']);
                        break;
                    }
                }
            }
        }

        // Get default listing settings
        public static function get_default_stripe_settings($settings=array(), $s=array()) {
            if(empty($s['enabled'])) $s['enabled'] = 'false';
            if(empty($s['conditions'])) $s['conditions'] = array(
                'conditions' => 'false', 
                'f1' => '', 
                'f2' => '', 
                'logic' => ''
            );
            if(empty($s['mode'])) $s['mode'] = 'payment'; // The mode of the Checkout Session. Required when using prices. Pass subscription if the Checkout Session includes at least one recurring item.
            if(empty($s['submit_type'])) $s['submit_type'] = 'auto'; // Describes the type of transaction being performed by Checkout in order to customize relevant text on the page, such as the submit button. submit_type can only be specified on Checkout Sessions in payment mode, but not Checkout Sessions in subscription mode.
            if(empty($s['cancel_url'])) $s['cancel_url'] = ''; // The URL the customer will be directed to if they decide to cancel payment and return to your website.
            if(empty($s['success_url'])) $s['success_url'] = ''; // The URL to which Stripe should send customers when payment is complete. If you’d like to use information from the successful Checkout Session on your page, read the guide on customizing your success page.
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
                'expiry' => 24, // Defaults to 24 hours, which is also the maximum
                //'subject' => esc_html__( 'Payment failed', 'super-forms' ),
                //'body' => sprintf( esc_html__( 'Payment failed please try again by clicking the below URL.%sThe below link will be valid for %s hours before your order is removed.%s%s', 'super-forms' ), "\n", '{stripe_retry_payment_expiry}', "\n\n", '<a href="{stripe_retry_payment_url}">{stripe_retry_payment_url}</a>' ),
                //'lineBreaks' => 'true'
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
                    'entry_status' => array(
                        array(
                            'status'=>'active',
                            'value'=>'active'
                        ),
                        array(
                            'status'=>'paused',
                            'value'=>'pending'
                        ),
                        array(
                            'status'=>'canceled',
                            'value'=>'trash'
                        )
                    ),
                    'post_status' => array(
                        array(
                            'status'=>'active',
                            'value'=>'publish'
                        ),
                        array(
                            'status'=>'paused',
                            'value'=>'pending'),
                        array(
                            'status'=>'canceled',
                            'value'=>'trash'
                        )
                    ),
                    'login_status' => array(
                        array(
                            'status'=>'active',
                            'value'=>'active'
                        ),
                        array(
                            'status'=>'paused',
                            'value'=>'pending'
                        ),
                        array(
                            'status'=>'canceled',
                            'value'=>'trash'
                        )
                    ),
                    'user_role' => array(
                        array(
                            'status'=>'active', 
                            'value'=>'subscriber'
                        )
                    )
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
                        'currency' => 'usd', // Three-letter ISO currency code. Must be a supported currency. In case you allow the user to choose a payment method via for instance a radio butotn element, make sure that you set the currency based on that since some payment methods like iDeal/Sofort require EUR currency. You can do this with the use of a variable field and retrieving it with a tag like {your_variable_currency_field_name_here}
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
            $s = apply_filters('super_stripe_default_settings_filter', $s);
            if(isset($settings)){
                if($settings==='') $settings = array();
                $s = array_merge($s, $settings);
            }
            return $s;
        }
        public static function redirect_to_stripe_checkout($x){
            extract( shortcode_atts( array(
                'sfsi'=>array(),
                'form_id'=>0,
                'sfsi_id'=>'',
                'post'=>array(), 
                'data'=>array(), 
                'settings'=>array(), 
                'entry_id'=>0, 
                'attachments'=>array()
            ), $x));
            $domain = home_url(); // e.g: 'http://domain.com';
            $home_url = trailingslashit($domain);
            if(empty($settings['_stripe'])) return true;
            $s = $settings['_stripe'];
            // Skip if Stripe checkout is not enabled
            if($s['enabled']!=='true') return true;
            // If conditional check is enabled
            $checkout = true;
            $c = $s['conditions'];
            if($c['enabled']==='true' && $c['logic']!==''){
                $logic = $c['logic'];
                $f1 = SUPER_Common::email_tags($c['f1'], $data, $settings);
                $f2 = SUPER_Common::email_tags($c['f2'], $data, $settings);
                $checkout = SUPER_Common::conditional_compare_check($f1, $logic, $f2);
            }
            if($checkout===false) return true;
            try{
                $api = self::setAppInfo();
            }catch(Exception $e){
                self::exceptionHandler($e);
            }
            // If the webhook ID is not configured or is empty, create a new one:
            $create_new_webhook = false;
            if(empty($api['webhook_id'])){
                $create_new_webhook = true;
            }else{
                // If it isn't empty, lookup the existing webhook, if we found one, we will check if all events exists
                // if not, update missing events, and create a new webhook if we couldn't find one
                try{
                    $webhook = \Stripe\WebhookEndpoint::retrieve($api['webhook_id'], array());
                }catch(Exception $e){
                    if($e->getCode()===0){
                        // Webhook doesn't exist, create a new webhook 
                        $create_new_webhook = true;
                    }else{
                        self::exceptionHandler($e);
                    }
                }
            }
            $global_settings = $api['global_settings'];
            if($create_new_webhook===true){
                $webhook = \Stripe\WebhookEndpoint::create(array(
                    'url' => $home_url.'sfstripe/webhook/', // super forms stripe webhook // 'https://example.com/my/webhook/endpoint',
                    'description' => 'This Webhook is used by Super Forms WordPress Form Builder',
                    'enabled_events' => self::$required_events,
                    'api_version' => \Stripe\Stripe::getApiVersion()
                ));
                $global_settings['stripe_' . $api['stripe_mode'] . '_webhook_id'] = $webhook->id;
                $global_settings['stripe_' . $api['stripe_mode'] . '_webhook_secret'] = $webhook->secret;
                update_option('super_settings', $global_settings);
            }else{
                // Check if the current webhook has all the events
                $eventMissing = false;
                foreach(self::$required_events as $k => $v){
                    if(!in_array($v, $webhook->enabled_events)) $eventMissing = true;
                }
                if($eventMissing){
                    try{
                        \Stripe\WebhookEndpoint::update($webhook->id, array('enabled_events'=>self::$required_events));
                    }catch(Exception $e){
                        self::exceptionHandler($e);
                    }
                }
            }
            $cancel_url = $home_url . 'sfssid/cancel/{CHECKOUT_SESSION_ID}';
            $success_url = $home_url . 'sfssid/success/{CHECKOUT_SESSION_ID}';
            $mode = SUPER_Common::email_tags( $s['mode'], $data, $settings );
            $customer_email = (isset($s['customer_email']) ? SUPER_Common::email_tags( $s['customer_email'], $data, $settings ) : '');
            $customer = '';
            if($s['use_logged_in_email']==='true'){
                // Check if user is logged in, or a newly user was registerd
                $user_id = get_current_user_id();
                if(!empty($sfsi['user_id'])){
                    $user_id = $sfsi['user_id'];
                }
                $email = '';
                if(!empty($user_id)){
                    $email = SUPER_Common::get_user_email($user_id);
                }
                if(empty($user_id)){
                    // Guest checkout
                    error_log('Stripe guest checkout');
                }else{
                    $sfsi['user_id'] = $user_id;
                    if(!empty($email)) $customer_email = $email;
                    try {
                        $create_new_customer = true;
                        // Check if user is already connected to a stripe customer
                        $super_stripe_cus = get_user_meta($user_id, 'super_stripe_cus', true);
                        if(!empty($super_stripe_cus)){
                            error_log('The current WP user has a Stripe customer ID connected, let\'s try to retrieve the Stripe customer based on this ID');
                            $customer = \Stripe\Customer::retrieve($super_stripe_cus);
                        }
                        if(empty($customer)){
                            error_log('Based on the existing Stripe ID the WP user has, we could not find a Stripe customer, the Stripe customer might have been deleted in the Stripe dashboard');
                            // Try to lookup by E-mail?
                            if($s['connect_stripe_email']==='true'){
                                error_log('Let\'s try to find Stripe customer based on the current WP user E-mail address');
                                $customers = \Stripe\Customer::all(array('email'=>$customer_email));
                                if(!empty($customers) && !empty($customers->data)){
                                    error_log('We found a Stripe customer based on this same E-mail address, we can use it, and update the connection with the WP user');
                                    $customer = $customers->data[0];
                                }
                            }
                        }
                        if(!empty($customer)){
                            error_log('We can connect this Stripe customer to this WP account');
                            // Check if customer was deleted
                            if(!empty($customer['deleted']) && $customer['deleted']==true){
                                // Customer was deleted, we should create a new
                                error_log('Except this Stripe customer was deleted, so in this case we will have to create a new one');
                            }else{
                                // The customer exists, make sure we do not create a new customer
                                error_log('This Stripe customer still exists and was not deleted yet');
                                $create_new_customer = false; 
                                update_user_meta($user_id, 'super_stripe_cus', $customer->id);
                                $customer = $customer->id;
                                error_log('Stripe customer '.$customer.' is connected to WP user: '.$user_id);
                            }
                        }
                        if($create_new_customer){
                            // Stripe customer doesn't exists, create a new Stripe customer with this E-mail address
                            error_log('Create a new Stripe customer with this E-mail address');
                            $customer = \Stripe\Customer::create(array('email' => $customer_email));
                            update_user_meta($user_id, 'super_stripe_cus', $customer->id);
                            $customer = $customer->id;
                            error_log('Newly created Stripe customer '.$customer.' is connected to WP user: '.$user_id);
                        }
                    }catch(Exception $e){
                        error_log('1 Stripe error occured');
                        error_log($e->getCode());
                        if($e->getCode()===0){
                            error_log('Customer does not exist, create a new one?');
                            // Customer doesn't exists, create a new customer
                            $customer = \Stripe\Customer::create(array('email' => $customer_email));
                            update_user_meta($user_id, 'super_stripe_cus', $customer->id);
                            $customer = $customer->id;
                        }else{
                            self::exceptionHandler($e);
                        }
                    }
                }
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
                'sfsi_id' => $sfsi_id
            );
            $home_cancel_url = (isset($s['cancel_url']) ? SUPER_Common::email_tags( $s['cancel_url'], $data, $settings ) : '');
            $home_success_url = (isset($s['success_url']) ? SUPER_Common::email_tags( $s['success_url'], $data, $settings ) : '');
            if($home_cancel_url==='') $home_cancel_url = $_SERVER['HTTP_REFERER'];
            if($home_success_url==='') $home_success_url = $_SERVER['HTTP_REFERER'];

            $sfsi = get_option( '_sfsi_' . $sfsi_id, array() );
            $sfsi['entry_id'] = $entry_id;
            $sfsi['stripe_home_cancel_url'] = $home_cancel_url;
            $sfsi['stripe_home_success_url'] = $home_success_url;
            SUPER_Common::setClientData( array( 'name'=>'stripe_home_cancel_url_'.$form_id, 'value'=>$home_cancel_url ) );
            SUPER_Common::setClientData( array( 'name'=>'stripe_home_success_url_'.$form_id, 'value'=>$home_success_url ) );
            update_option('_sfsi_' . $sfsi_id, $sfsi );
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
                    $shipping_options['shipping_rate_data']['fixed_amount']['amount'] = SUPER_Common::tofloat($shipping_options['shipping_rate_data']['fixed_amount']['amount']) * 100;
                }
            }
            // A set of key-value pairs that you can attach to a source object. 
            // It can be useful for storing additional information about the source in a structured format.
            foreach($metadata as $k => $v){
                if(is_array($v)) $metadata[$k] = SUPER_Common::safe_json_encode($v);
            }
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
                    $v['price_data']['unit_amount_decimal'] = SUPER_Common::tofloat($v['price_data']['unit_amount_decimal']) * 100;
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
                        $v['price_data']['unit_amount_decimal'] = SUPER_Common::tofloat($v['price_data']['unit_amount_decimal']) * 100;
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
            // Get webhook ID and secret
            $webhookId = (isset($global_settings['stripe_' . $api['stripe_mode'] . '_webhook_id']) ? $global_settings['stripe_' . $api['stripe_mode'] . '_webhook_id'] : '');
            $webhookSecret = (isset($global_settings['stripe_' . $api['stripe_mode'] . '_webhook_secret']) ? $global_settings['stripe_' . $api['stripe_mode'] . '_webhook_secret'] : '');
            if(empty($webhookId)){
                $msg = sprintf(
                    esc_html( 'Please enter your webhook ID under:%s%sSettings > Stripe Checkout%s.%sIt should start with `we_`.%sYou can find your Webhook ID via %swebhook settings%s.', 'super-forms' ), 
                    '<br />',
                    '<a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#stripe-checkout') . '">', '</a>', 
                    '<br /><br />', '<br /><br />',
                    '<a target="_blank" href="https://dashboard.stripe.com/webhooks">', '</a>'
                );
                $e = new Exception($msg);
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
                self::exceptionHandler($e, $metadata);
                die();
            }
            // Check if this webhook has a correct endpoint URL
            $webhook = \Stripe\WebhookEndpoint::retrieve($webhookId, []);
            $endsWith = 'sfstripe/webhook/';
            // Check if $webhookUrl ends with $endsWith
            if(substr(trailingslashit($webhook['url']), -strlen($endsWith))!==$endsWith){
                $msg = sprintf(
                    esc_html( 'Please update your Webhook endpoint so that it points to the following URL:%sYou can change this via %swebhook settings%s.', 'super-forms' ), 
                    '<br /><br /><code> ' . $home_url . 'sfstripe/webhook</code><br /><br />', // super forms stripe webhook e.g: https://domain.com/sfstripe/webhook will be converted into https://domain.com/index.php?sfstripewebhook=true 
                    '<a target="_blank" href="https://dashboard.stripe.com/webhooks/'. $webhook['id'].'">', '</a>'
                );
                $e = new Exception($msg);
                self::exceptionHandler($e, $metadata);
                die();

            }
            // Try to start a Checkout Session
            try {
                // Use Stripe's library to make requests...
                $stripeData = array(
                    // [required conditionally] The mode of the Checkout Session. Required when using prices mode. Pass subscription if the Checkout Session includes at least one recurring item.
                    'mode' => $mode, //'payment', // `payment`, `subscription`
                    // [required] The URL the customer will be directed to if they decide to cancel payment and return to your website.
                    'cancel_url' => $cancel_url,
                    // [required] The URL to which Stripe should send customers when payment is complete. If you’d like to use information from the successful Checkout Session on your page, read the guide on
                    'success_url' => $success_url,
                    // Example success page could be:
                    // ```php
                    // $stripe_session = \Stripe\Checkout\Session::retrieve($request->get('session_id'));
                    // $customer = \Stripe\Customer::retrieve($stripe_session->customer);
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
                    // 'expires_at' => current_time('timestamp') + (3600 * 0.5), // Configured to expire after 30 min. 
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


                // [optional] The coupon or promotion code to apply to this Session. Currently, only up to one may be specified.
                //'discounts' => [
                //    'coupon' => '', // The ID of the coupon to apply to this Session.
                //    'promotion_code' => '' // The ID of a promotion code to apply to this Session.
                //]
                $coupon_id = 0;
                if($s['discounts']['type']==='existing_coupon'){
                    $stripeData['coupon'] = $s['discounts']['coupon'];
                }
                if($s['discounts']['type']==='existing_promotion_code'){
                    $stripeData['promotion_code'] = $s['discounts']['promotion_code'];
                }
                if($s['discounts']['type']==='new'){
                    // Create a coupon
                    $couponParams = array();
                    $couponName = $s['discounts']['new']['name'];
                    if(!empty($couponName)) $couponParams['name'] = $couponName; // Name of the coupon displayed to customers on, for instance invoices, or receipts. By default the id is shown if name is not set.
                    if($s['discounts']['new']['type']==='percent_off'){
                        $couponParams['percent_off'] = SUPER_Common::tofloat($s['discounts']['new']['percent_off']); // float A positive float larger than 0, and smaller or equal to 100, that represents the discount the coupon will apply (required if amount_off is not passed).
                        $couponParams['duration'] = 'once'; // forever, once, repeating Change this if you want the coupon to have a different duration
                        // 'duration_in_months' => 3 // integer Required only if duration is repeating, in which case it must be a positive integer that specifies the number of months the discount will be in effect.
                    }
                    if($s['discounts']['new']['type']==='amount_off'){
                        $couponParams['amount_off'] = SUPER_Common::tofloat($s['discounts']['new']['amount_off'])*100; // A positive integer representing the amount to subtract from an invoice total (required if percent_off is not passed).
                        $couponParams['duration'] = 'once'; // forever, once, repeating Change this if you want the coupon to have a different duration
                        $couponParams['currency'] = $s['discounts']['new']['currency']; // forever, once, repeating Change this if you want the coupon to have a different duration
                        // 'duration_in_months' => 3 // integer Required only if duration is repeating, in which case it must be a positive integer that specifies the number of months the discount will be in effect.
                    }
                    $coupon = \Stripe\Coupon::create($couponParams);
                    $stripeData['discounts'] = array(array('coupon' => $coupon->id));
                }
                function array_remove_empty($haystack){
                    foreach ($haystack as $key => $value) {
                        if(is_array($value)) $haystack[$key] = array_remove_empty($haystack[$key]);
                        if(empty($haystack[$key])) unset($haystack[$key]);
                    }
                    return $haystack;
                }
                if($mode==='subscription'){
                    unset($stripeData['customer_creation']);
                    unset($stripeData['shipping_options']);
                }
                if($mode==='setup'){ // even though `setup` isn't an option right now we know that shipping_options isn't allowed to be passed
                    unset($stripeData['shipping_options']);
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
                if(isset($stripeData['submit_type']) && $mode!=='subscription'){
                    unset($stripeData['subscription_data']);
                }
                //if($stripeData['submit_type']!=='payment'){
                //    unset($stripeData['subscription_data']);
                //}
                $stripeData = array_remove_empty($stripeData);
                $stripeData = apply_filters( 'super_stripe_checkout_session_create_data_filter', $stripeData );
                // Append stripe data to submission info
                $sfsi = get_option( '_sfsi_' . $sfsi_id, array() );
                $sfsi['stripeData'] = $stripeData;
                update_option('_sfsi_' . $sfsi_id, $sfsi );
                // Create the checkout session via Stripe API
                $expires_at = current_time('timestamp') - (3600 * 1.5);
                $stripeData['expires_at'] = $expires_at;
                $checkout_session = \Stripe\Checkout\Session::create($stripeData);
            } catch( Exception $e ){
                if($stripeData['customer']!=='' && strpos($e->getMessage(), 'You cannot combine currencies on a single customer.')===0){
                    // This seems to be the only way to detect if this error occurs, stripe doesn't return a specific Error code...
                    // Try to make the same request
                    try {
                        error_log('Stripe: '.$e->getMessage());
                        unset($stripeData['customer']);
                        unset($stripeData['customer_update']);
                        $stripeData['customer_email'] = $customer_email;
                        // error_log('retry the checkout session, but this time without passing an existing customer, because of the currency conflict.');
                        // error_log('Create a new Stripe customer with this E-mail address');
                        // if($create_new_customer){
                        //     error_log('test1');
                        //     error_log($customer->id);
                        // }else{
                        //     error_log('test2');
                        //     $customer = \Stripe\Customer::create(array('email' => $customer_email));
                        //     error_log('created a new customer just for this checkout since it uses a different currency');
                        //     // Do not update connection with user, keep the existing one
                        //     // update_user_meta($user_id, 'super_stripe_cus', $customer->id);
                        // }
                        // $customer = $customer->id;
                        $checkout_session = \Stripe\Checkout\Session::create($stripeData);
                    } catch( Exception $e ){
                        self::exceptionHandler($e, $metadata);
                    }
                }else{
                    self::exceptionHandler($e, $metadata);
                }
            }
            // Redirect to Stripe checkout page
            $back_url = $home_url . 'sfssid/cancel/'.$checkout_session->id;
            SUPER_Common::output_message( array(
                'error'=>false, 
                'msg' => '', 
                'back_url' => $back_url,
                'redirect' => $checkout_session->url,
                'form_id' => absint($sfsi['form_id'])
            ));
            die();
        }
        public static function setAppInfo(){
            require_once 'stripe-php/init.php';
            \Stripe\Stripe::setAppInfo('Super Forms - Stripe Add-on', SUPER_VERSION, 'https://super-forms.com');
            $global_settings = SUPER_Common::get_global_settings();
            if(!empty($global_settings['stripe_mode']) && $global_settings['stripe_mode']!=='live' ) {
                $global_settings['stripe_mode'] = 'sandbox';
            }else{
                $global_settings['stripe_mode'] = 'live';
            }
            $public_key = (isset($global_settings['stripe_' . $global_settings['stripe_mode'] . '_public_key']) ? $global_settings['stripe_' . $global_settings['stripe_mode'] . '_public_key'] : '');
            $secret_key = (isset($global_settings['stripe_' . $global_settings['stripe_mode'] . '_secret_key']) ? $global_settings['stripe_' . $global_settings['stripe_mode'] . '_secret_key'] : '');
            $webhook_id = (isset($global_settings['stripe_' . $global_settings['stripe_mode'] . '_webhook_id']) ? $global_settings['stripe_' . $global_settings['stripe_mode'] . '_webhook_id'] : '');
            $webhook_secret = (isset($global_settings['stripe_' . $global_settings['stripe_mode'] . '_webhook_secret']) ? $global_settings['stripe_' . $global_settings['stripe_mode'] . '_webhook_secret'] : '');
            if(empty($secret_key)){
                $mode = 'Sandbox';
                if($global_settings['stripe_mode']==='live') $mode = 'Live';
                $msg = sprintf( esc_html__( 'Stripe %s API key not configured, please enter your API key under %sSuper Forms > Settings > Stripe Checkout%s', 'super-forms' ), $mode, '<a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#stripe-checkout') . '">', '</a>' );
                $e = new Exception($msg);
                self::exceptionHandler($e);
            }
            try{
                \Stripe\Stripe::setApiKey($secret_key);
            }catch(Exception $e){
                self::exceptionHandler($e);
            }
            return array(
                'global_settings'=>$global_settings,
                'stripe_mode'=>$global_settings['stripe_mode'],
                'public_key'=>$public_key, 
                'secret_key'=>$secret_key, 
                'webhook_id'=>$webhook_id,
                'webhook_secret'=>$webhook_secret
            );
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
        public static function exceptionHandler($e, $metadata=array()){
            $form_id = 0;
            error_log('Super Forms - Stripe checkout session error code: '.$e->getCode());
            error_log($e);
            if(!empty($metadata['sfsi_id'])) $form_id = SUPER_Common::cleanupFormSubmissionInfo($metadata['sfsi_id'], '');
            if(!empty($metadata['sf_id'])) $form_id = $metadata['sf_id'];
            SUPER_Common::output_message( array(
                'msg' => $e->getMessage(),
                'form_id' => absint($form_id)
            ));
            die();
        }
        public static function fulfillOrder($atts){
            extract($atts); 
            $sfsi = get_option( '_sfsi_' . $sfsi_id, array() );
            error_log('8.0: '.json_encode($sfsi));
            if(count($sfsi)>0){
                error_log('8.1: '.json_encode($sfsi));
                extract($sfsi);
                error_log('8.2: '.json_encode($sfsi));
            }
            $settings = SUPER_Common::get_form_settings($form_id);
            $stripe_settings = self::get_default_stripe_settings($settings);
            $entry_id = (!empty($entry_id) ? absint($entry_id) : 0);
            $registered_user_id = (!empty($registered_user_id) ? absint($registered_user_id) : 0);
            $created_post = (!empty($created_post) ? absint($created_post) : 0);
            $payment_intent = $stripe_session['payment_intent'];
            $invoice = $stripe_session['invoice'];
            $customer = $stripe_session['customer'];
            $subscription = $stripe_session['subscription'];
            if(!empty($entry_id)){
                // Update entry status after payment completed?
                $stripe_settings['update_entry_status'] = SUPER_Common::email_tags(trim($stripe_settings['update_entry_status']), $data, $settings);
                // Update contact entry status after succesfull payment
                if(!empty($stripe_settings['update_entry_status'])) update_post_meta($entry_id, '_super_contact_entry_status', $stripe_settings['update_entry_status']);
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
                $stripe_settings['register_login']['update_login_status'] = SUPER_Common::email_tags(trim($stripe_settings['register_login']['update_login_status']), $data, $settings);
                if(!empty($stripe_settings['register_login']['update_login_status'])){
                    // Update login status
                    if(!empty($stripe_settings['register_login']['update_login_status'])) update_user_meta($registered_user_id, 'super_user_login_status', $stripe_settings['register_login']['update_login_status']);
                }
                // Update registered user role after payment completed?
                $stripe_settings['register_login']['update_user_role'] = SUPER_Common::email_tags(trim($stripe_settings['register_login']['update_user_role']), $data, $settings);
                if(!empty($stripe_settings['register_login']['update_user_role'])){
                    // Update user role
                    $userdata = array('ID' => $registered_user_id, 'role' => $stripe_settings['register_login']['update_user_role']);
                    wp_update_user($userdata);
                }
            }
            if(!empty($created_post)){
                $stripe_settings['frontend_posting']['update_post_status'] = SUPER_Common::email_tags(trim($stripe_settings['frontend_posting']['update_post_status']), $data, $settings);
                if(!empty($stripe_settings['frontend_posting']['update_post_status'])){
                    // Update created post status after payment completed?
                    wp_update_post(array('ID' => $created_post, 'post_status' => $stripe_settings['frontend_posting']['update_post_status']));
                }
            }
            // Fulfill the purchase
            SUPER_Common::triggerEvent('stripe.fulfill_order', array('sfsi_id'=>$sfsi_id, 'stripe_settings'=>$stripe_settings, 'stripe_session'=>$stripe_session));
            //SUPER_Common::triggerEvent('stripe.fulfill_order', $x); // $x = array('form_id'=>$form_id, 's'=>$s, 'stripe_session'=>$stripe_session, 'sfsi'=>$sfsi)
            // Delete submission info
            delete_option( '_sfsi_' . $sfsi_id );
        }
        public static function paymentFailed($atts){
            // tmp extract($atts); 
            // tmp $sfsi = get_option( '_sfsi_' . $sfsi_id, array() );
            // tmp error_log('8.0: '.json_encode($sfsi));
            // tmp if(count($sfsi)>0){
            // tmp     error_log('8.1: '.json_encode($sfsi));
            // tmp     extract($sfsi);
            // tmp     error_log('8.2: '.json_encode($sfsi));
            // tmp }
            // tmp $settings = SUPER_Common::get_form_settings($form_id);
            // tmp $stripe_settings = self::get_default_stripe_settings($settings);
            // tmp $entry_id = (!empty($entry_id) ? absint($entry_id) : 0);
            // tmp $registered_user_id = (!empty($registered_user_id) ? absint($registered_user_id) : 0);
            // tmp $created_post = (!empty($created_post) ? absint($created_post) : 0);
            // tmp $payment_intent = $stripe_session['payment_intent'];
            // tmp $invoice = $stripe_session['invoice'];
            // tmp $customer = $stripe_session['customer'];
            // tmp $subscription = $stripe_session['subscription'];
            // tmp if(!empty($entry_id)){
            // tmp     // Update entry status after payment completed?
            // tmp     $stripe_settings['update_entry_status'] = SUPER_Common::email_tags(trim($stripe_settings['update_entry_status']), $data, $settings);
            // tmp     // Update contact entry status after succesfull payment
            // tmp     if(!empty($stripe_settings['update_entry_status'])) update_post_meta($entry_id, '_super_contact_entry_status', $stripe_settings['update_entry_status']);
            // tmp     // Connect Stripe details to this entry
            // tmp     // @TODO --- === > TEST BELOW
            // tmp     update_post_meta($entry_id, '_super_stripe_connections', 
            // tmp         array(
            // tmp             'payment_intent' => $payment_intent,
            // tmp             'invoice' => $invoice,
            // tmp             'customer' => $customer,
            // tmp             'subscription' => $subscription
            // tmp         )
            // tmp     );
            // tmp }
            // tmp if(!empty($registered_user_id)){
            // tmp     // Update registered user login status after payment completed?
            // tmp     $stripe_settings['register_login']['update_login_status'] = SUPER_Common::email_tags(trim($stripe_settings['register_login']['update_login_status']), $data, $settings);
            // tmp     if(!empty($stripe_settings['register_login']['update_login_status'])){
            // tmp         // Update login status
            // tmp         if(!empty($stripe_settings['register_login']['update_login_status'])) update_user_meta($registered_user_id, 'super_user_login_status', $stripe_settings['register_login']['update_login_status']);
            // tmp     }
            // tmp     // Update registered user role after payment completed?
            // tmp     $stripe_settings['register_login']['update_user_role'] = SUPER_Common::email_tags(trim($stripe_settings['register_login']['update_user_role']), $data, $settings);
            // tmp     if(!empty($stripe_settings['register_login']['update_user_role'])){
            // tmp         // Update user role
            // tmp         $userdata = array('ID' => $registered_user_id, 'role' => $stripe_settings['register_login']['update_user_role']);
            // tmp         wp_update_user($userdata);
            // tmp     }
            // tmp }
            // tmp if(!empty($created_post)){
            // tmp     $stripe_settings['frontend_posting']['update_post_status'] = SUPER_Common::email_tags(trim($stripe_settings['frontend_posting']['update_post_status']), $data, $settings);
            // tmp     if(!empty($stripe_settings['frontend_posting']['update_post_status'])){
            // tmp         // Update created post status after payment completed?
            // tmp         wp_update_post(array('ID' => $created_post, 'post_status' => $stripe_settings['frontend_posting']['update_post_status']));
            // tmp     }
            // tmp }
            // tmp // Fulfill the purchase
            // tmp SUPER_Common::triggerEvent('stripe.fulfill_order', array('sfsi_id'=>$sfsi_id, 'stripe_settings'=>$stripe_settings, 'stripe_session'=>$stripe_session));
            // tmp //SUPER_Common::triggerEvent('stripe.fulfill_order', $x); // $x = array('form_id'=>$form_id, 's'=>$s, 'stripe_session'=>$stripe_session, 'sfsi'=>$sfsi)
            // tmp // Delete submission info
            // tmp delete_option( '_sfsi_' . $sfsi_id );
        }
        public static function add_tab($tabs){
            $tabs['stripe'] = 'Stripe';
            return $tabs;
        }
        public static function add_tab_content($atts){
            extract($atts);
            $slug = SUPER_Stripe()->add_on_slug;
            error_log('add_tab_content()');
            error_log(json_encode($stripe));
            $s = self::get_default_stripe_settings($stripe);
            $logic = array( '==' => '== Equal', '!=' => '!= Not equal', '??' => '?? Contains', '!!' => '!! Not contains', '>'  => '&gt; Greater than', '<'  => '&lt;  Less than', '>=' => '&gt;= Greater than or equal to', '<=' => '&lt;= Less than or equal');
            $statuses = SUPER_Settings::get_entry_statuses();
            $entryStatusesValues = array(
                array( 'v'=>'delete', 'i'=>'(to permanently delete the entry)'),
                array( 'v'=>'trash', 'i'=>'(to trash the entry)')
            );
            foreach($statuses as $k => $v) {
                if($k==='') continue;
                $entryStatusesValues[] = array('v'=>$k);
            }
            $postStatusesValues = array();
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
                $postStatusesValues[] = array('v'=>$k, 'i'=>$v);
            }

            global $wp_roles;
            $all_roles = $wp_roles->roles;
            $editable_roles = apply_filters( 'editable_roles', $all_roles );
            $roleValues = array();
            foreach( $editable_roles as $k => $v ) {
                $roleValues[] = array('v'=>$k);
            }
            $userLoginStatusesValues = array();
            $statuses = array(
                'active' => esc_html__( 'Active (default)', 'super-forms' ),
                'pending' => esc_html__( 'Pending', 'super-forms' ),
                'paused' => esc_html__( 'Paused', 'super-forms' ),
                'blocked' => esc_html__( 'Blocked', 'super-forms' ),
                'payment_past_due' => esc_html__( 'Payment past due', 'super-forms' ),
                'signup_payment_processing' => esc_html__( 'Signup payment processing', 'super-forms' )
                //'payment_processing' => esc_html__( 'Payment processing', 'super-forms' ),
                //'payment_required' => esc_html__( 'Payment required', 'super-forms' ),
            );
            foreach($statuses as $k => $v) {
                $userLoginStatusesValues[] = array('v'=>$k, 'i'=>$v);
            }

            $nodes = array(
                array(
                    'notice' => 'hint',
                    'content' => '<strong>'.esc_html__('Note', 'super-forms').':</strong> ' . sprintf( esc_html__( 'Make sure to enter your Stripe API credentials via %sSuper Forms > Settings > Stripe Checkout%s', 'super-forms' ), '<a target="_blank" href="' . esc_url(admin_url()) . 'admin.php?page=super_settings#stripe-checkout">', '</a>' )
                ),
                array(
                    'notice' => 'hint', // hint/info
                    'content' => '<strong>'.esc_html__('Tip', 'super-forms').':</strong> ' . sprintf( esc_html__( 'You can use field {tags} to configure the below settings based on user input.', 'super-forms' ), '<a target="_blank" href="' . esc_url(admin_url()) . 'admin.php?page=super_settings#stripe-checkout">', '</a>' )
                ),
                array(
                    'name' => 'enabled',
                    'title' => esc_html__( 'Enable Stripe checkout for this form', 'super-forms' ),
                    'type' => 'checkbox',
                    'default' => '',
                    'nodes' => array(
                        array(
                            'sub' => true, // sfui-sub-settings
                            'filter' => 'enabled;true',
                            'nodes' => array(
                                array(
                                    //'width_auto' => false, // 'sfui-width-auto'
                                    'wrap' => false,
                                    'group' => true, // sfui-setting-group
                                    'group_name' => 'conditions',
                                    'inline' => true, // sfui-inline
                                    //'vertical' => true, // sfui-vertical
                                    'nodes' => array(
                                        array(
                                            'name' => 'enabled',
                                            'type' => 'checkbox',
                                            'default' => 'false',
                                            'title' => esc_html__( 'Conditionally checkout to Stripe', 'super-forms' ),
                                            'nodes' => array(
                                                array(
                                                    'sub' => true, // sfui-sub-settings
                                                    //'group' => true, // sfui-setting-group
                                                    'inline' => true, // sfui-inline
                                                    //'vertical' => true, // sfui-vertical
                                                    'filter' => 'conditions.enabled;true',
                                                    'nodes' => array(
                                                        array( 'name' => 'f1', 'type' => 'text', 'default' => '', 'placeholder' => 'e.g. {tag}',),
                                                        array( 'name' => 'logic', 'type' => 'select', 'options' => $logic, 'default' => '',),
                                                        array( 'name' => 'f2', 'type' => 'text', 'default' => '', 'placeholder' => 'e.g. true')
                                                    )
                                                )
                                            )
                                        )
                                    )
                                ),
                                array(
                                    'toggle' => true,
                                    'title' => esc_html__( 'General settings', 'super-forms' ),
                                    'vertical' => true,
                                    'nodes' => array(
                                        array(
                                            'name' => 'mode',
                                            'title' => esc_html__( 'The mode of the Checkout Session', 'super-forms' ),
                                            'accepted_values' => array(array('v'=>'payment', 'i'=>'(for one time payments)'), array('v'=>'subscription', 'i'=>'(for recurring payments)')),
                                            'type' => 'text',
                                            'default' => 'payment'
                                        ),
                                        array(
                                            'wrap' => false,
                                            'group' => true,
                                            'group_name' => 'subscription_data',
                                            'filter' => 'mode;subscription',
                                            'vertical' => true,
                                            'nodes' => array(
                                                array(
                                                    'name' => 'trial_period_days',
                                                    'title' => esc_html__( 'Trial period in days', 'super-forms' ),
                                                    'subline' => esc_html__( 'Integer representing the number of trial period days before the customer is charged for the first time. Has to be at least 1. (leave blank for no trial period)', 'super-forms' ),
                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), '15' ),
                                                    'type' => 'text'
                                                ),
                                                array(
                                                    'name' => 'description',
                                                    'title' => esc_html__( 'The subscription’s description, meant to be displayable to the customer.', 'super-forms' ),
                                                    'subline' => esc_html__( 'Use this field to optionally store an explanation of the subscription for rendering in Stripe hosted surfaces.', 'super-forms' ),
                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'Website updates and maintenance' ),
                                                    'type' => 'text'
                                                )
                                            )
                                        ),
                                        array(
                                            'name' => 'payment_method_types',
                                            'title' => esc_html__( 'Payment methods', 'super-forms' ),
                                            'subline' => esc_html__( 'A list of the types of payment methods this Checkout Session can accept. Separate each by comma.', 'super-forms' ),
                                            'placeholder' => esc_html__( 'e.g. card,ideal', 'super-forms' ),
                                            'accepted_values' => array(array('v'=>'card'), array('v'=>'paypal'), array('v'=>'acss_debit'), array('v'=>'afterpay_clearpay'), array('v'=>'alipay'), array('v'=>'au_becs_debit'), array('v'=>'bacs_debit'), array('v'=>'bancontact'), array('v'=>'boleto'), array('v'=>'eps'), array('v'=>'fpx'), array('v'=>'giropay'), array('v'=>'grabpay'), array('v'=>'ideal'), array('v'=>'klarna'), array('v'=>'konbini'), array('v'=>'oxxo'), array('v'=>'p24'), array('v'=>'paynow'), array('v'=>'sepa_debit'), array('v'=>'sofort'), array('v'=>'us_bank_account'), array('v'=>'wechat_pay')),
                                            'type' => 'text',
                                            'default' => 'card',
                                            'i18n' => true
                                        ),
                                        array(
                                            'name' => 'customer_email',
                                            'title' => esc_html__( 'Customer E-mail', 'super-forms' ),
                                            'subline' => esc_html__( 'If provided, this value will be used when the Customer object is created. If not provided, customers will be asked to enter their email address. Use this parameter to prefill customer data if you already have an email on file.', 'super-forms' ),
                                            'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), '{email}' ),
                                            'type' => 'text',
                                            'default' => ''
                                        ),
                                        array(
                                            'name' => 'use_logged_in_email',
                                            'title' => esc_html__( 'Override with currently logged in or newly registered user E-mail address (recommended)', 'super-forms' ),
                                            'type' => 'checkbox',
                                            'default' => 'true'
                                        ),
                                        array(
                                            'name' => 'connect_stripe_email',
                                            'title' => esc_html__( 'If a Stripe user with this E-mail address already exists connect it to the WordPress user (recommended)', 'super-forms' ),
                                            'type' => 'checkbox',
                                            'default' => 'true'
                                        ),
                                        array(
                                            'name' => 'cancel_url',
                                            'title' => esc_html__( 'Cancel URL', 'super-forms' ),
                                            'subline' => esc_html__( 'The URL the customer will be directed to if they decide to cancel payment and return to your website.', 'super-forms' ),
                                            'placeholder' => esc_html__( 'Leave blank to redirect back to the page with the form', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => ''
                                        ),
                                        array(
                                            'name' => 'success_url',
                                            'title' => esc_html__( 'Success URL', 'super-forms' ),
                                            'subline' => esc_html__( 'The URL to which Stripe should send customers when payment is complete. If you\'d like to use information from the successful Checkout Session on your page, read the guide on customizing your success page.', 'super-forms' ),
                                            'placeholder' => esc_html__( 'Leave blank to redirect back to the page with the form', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => ''
                                        ),
                                        array(
                                            'wrap' => false,
                                            'group' => true,
                                            'group_name' => 'automatic_tax',
                                            'vertical' => true,
                                            'nodes' => array(
                                                array(
                                                    'name' => 'enabled',
                                                    'title' => esc_html__( 'Enable automatic Tax', 'super-forms' ),
                                                    'accepted_values' => array(array('v'=>'true'), array('v'=>'false')),
                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'true' ),
                                                    'type' => 'text',
                                                    'default' => 'true'
                                                )
                                            )
                                        ),
                                        array(
                                            'wrap' => false,
                                            'group' => true,
                                            'group_name' => 'tax_id_collection',
                                            'vertical' => true,
                                            'nodes' => array(
                                                array(
                                                    'name' => 'enabled',
                                                    'title' => esc_html__( 'Enable Tax ID collection (allows users to purchase as a business)', 'super-forms' ),
                                                    'accepted_values' => array(array('v'=>'true'), array('v'=>'false')),
                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'true' ),
                                                    'type' => 'text',
                                                    'default' => 'true'
                                                )
                                            )
                                        ),
                                        array(
                                            'name' => 'billing_address_collection',
                                            'title' => esc_html__( 'Collect customer billing address', 'super-forms' ),
                                            'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'required' ),
                                            'accepted_values' => array(array('v'=>'auto'), array('v'=>'required')),
                                            'type' => 'text',
                                            'default' => 'required'
                                        ),
                                        array(
                                            'wrap' => false,
                                            'group' => true,
                                            'group_name' => 'phone_number_collection',
                                            'vertical' => true,
                                            'nodes' => array(
                                                array(
                                                    'name' => 'enabled',
                                                    'title' => esc_html__( 'Collect customer phone number', 'super-forms' ),
                                                    'accepted_values' => array(array('v'=>'false'), array('v'=>'true')),
                                                    'type' => 'text',
                                                    'default' => 'false'
                                                ),
                                                array(
                                                    'notice' => 'info', // hint/info
                                                    'content' => '<strong>' . esc_html__('Note', 'super-forms') . ':</strong> ' . sprintf( esc_html__( 'We recommend that you review your privacy policy and check with your legal contacts before enabling phone number collection. Learn more about %scollecting phone numbers with Checkout%s.', 'super-forms' ), '<a href="https://stripe.com/docs/payments/checkout/phone-numbers">', '</a>')
                                                )
                                            )
                                        ),
                                        array(
                                            'name' => 'invoice_creation',
                                            'title' => esc_html__( 'Create invoice', 'super-forms' ),
                                            'accepted_values' => array(array('v'=>'true'), array('v'=>'false')),
                                            'type' => 'text',
                                            'default' => 'true'
                                        ),
                                        array(
                                            'notice' => 'info', // hint/info
                                            'content' => sprintf( esc_html__( 'The `Create invoice` setting only affects Checkout Sessions with `payment` as mode. Set this to `false` when you are creating invoices outside of Stripe. Set this to `true` if you want Stripe to create invoices automatically for one-time payments. To send invoice summary emails to your customer, you must make sure you enable the %1$sEmail customers about successful payments%2$s in your Stripe Dashboard. You can also prevent Stripe from sending these emails by %1$sdisabling the setting%2$s in your Stripe Dashboard. If a delayed payment method is used, the invoice will be send after successful payment.', 'super-forms' ), '<a href="https://dashboard.stripe.com/settings/emails" target="_blank">', '</a>')
                                        )
                                    )
                                ),
                                array(
                                    'toggle' => true,
                                    'title' => esc_html__( 'Checkout line items', 'super-forms' ),
                                    'vertical' => true,
                                    'nodes' => array(
                                        array(
                                            'name' => 'line_items',
                                            'type' => 'repeater',
                                            'nodes' => array( // repeater item
                                                array(
                                                    'name' => 'quantity',
                                                    'title' => esc_html__( 'Quantity', 'super-forms' ),
                                                    'placeholder' => esc_html__( 'e.g. 1', 'super-forms' ),
                                                    'type' => 'text',
                                                    'default' => '1'
                                                ),
                                                array(
                                                    'name' => 'type',
                                                    'placeholder' => esc_html__( 'e.g. 1', 'super-forms' ),
                                                    'type' => 'radio',
                                                    'options' => array(
                                                        'price' => esc_html__( 'Based on existing Stripe Price or Plan ID (recommended)', 'super-forms' ),
                                                        'price_data' => esc_html__( 'Create new price object', 'super-forms' ),
                                                    ),
                                                    'default' => 'price'
                                                ),
                                                array(
                                                    'name' => 'price',
                                                    'title' => esc_html__( 'Product price/plan ID', 'super-forms' ),
                                                    'subline' => sprintf( esc_html__( 'You can create a new product and price via the Stripe %sDashboard%s.', 'super-forms' ), '<a target="_blank" href="https://dashboard.stripe.com/products">', '</a>' ),
                                                    'placeholder' => esc_html__( 'e.g. price_XXXX', 'super-forms' ),
                                                    'type' => 'text',
                                                    'default' => '',
                                                    'filter' => 'type;price',
                                                    'i18n' => true
                                                ),
                                                array(
                                                    'wrap' => false,
                                                    'group' => true,
                                                    'group_name' => 'price_data',
                                                    'vertical' => true,
                                                    'filter' => 'type;price_data',
                                                    'nodes' => array(
                                                        array(
                                                            'name' => 'type',
                                                            'type' => 'radio',
                                                            'options' => array(
                                                                'product' => esc_html__( 'Use an existing product ID which the price will belong to', 'super-forms' ),
                                                                'product_data' => esc_html__( 'Create new Stripe product on the fly', 'super-forms' )
                                                            ),
                                                            'default' => 'product'
                                                        ),
                                                        array(
                                                            'name' => 'product',
                                                            'title' => esc_html__( 'Product ID', 'super-forms' ),
                                                            'subline' => esc_html__( 'Enter an already existing Stripe product ID that this price will belong to', 'super-forms' ),
                                                            'placeholder' => esc_html__( 'e.g. prod_XXXXXXXXXXX', 'super-forms' ),
                                                            'type' => 'text',
                                                            'default' => '',
                                                            'filter' => 'price_data.type;product',
                                                            'i18n' => true
                                                        ),
                                                        array(
                                                            'wrap' => false,
                                                            'group' => true,
                                                            'group_name' => 'product_data',
                                                            'vertical' => true,
                                                            'filter' => 'price_data.type;product_data',
                                                            'nodes' => array(
                                                                array(
                                                                    'name' => 'name',
                                                                    'subline' => esc_html__( 'Product name', 'super-forms' ),
                                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'Notebook' ),
                                                                    'type' => 'text',
                                                                    'default' => ''
                                                                ),
                                                                array(
                                                                    'name' => 'description',
                                                                    'subline' => esc_html__( 'Description', 'super-forms' ),
                                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'Intel i9, 8GB RAM' ),
                                                                    'type' => 'text',
                                                                    'default' => ''
                                                                ),
                                                                array(
                                                                    'name' => 'tax_code',
                                                                    'subline' => sprintf( esc_html__( 'Tax category code ID. %sFind a tax category%s. Your default tax category is used if you don’t provide one when creating a transaction with Stripe Tax enabled. You can update this in your %stax settings%s.', 'super-forms' ), '<a target="_blank" href="https://stripe.com/docs/tax/tax-categories">', '</a>', '<a target="_blank" href="https://dashboard.stripe.com/settings/tax">', '</a>' ),
                                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'txcd_99999999 (Tangible Goods)' ),
                                                                    'type' => 'text',
                                                                    'default' => ''
                                                                )
                                                            )
                                                        ),
                                                        array(
                                                            'name' => 'unit_amount_decimal',
                                                            'title' => esc_html__( 'Unit amount', 'super-forms' ),
                                                            'subline' => esc_html__( 'Define a price as float value (only dot is accepted as decimal separator)', 'super-forms' ),
                                                            'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), '65.95' ),
                                                            'type' => 'text',
                                                            'default' => '10.95'
                                                        ),
                                                        array(
                                                            'name' => 'currency',
                                                            'title' => esc_html__( 'Currency', 'super-forms' ),
                                                            'subline' => sprintf( esc_html__( 'Three-letter ISO currency code. Must be a %ssupported currency%s.', 'super-forms' ), '<a target="_blank" href="https://stripe.com/docs/currencies">', '</a>' ),
                                                            'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'USD or EUR' ),
                                                            'type' => 'text',
                                                            'default' => 'USD'
                                                        ),
                                                        array(
                                                            'notice' => 'hint', // hint/info
                                                            'content' => '<strong>'.esc_html__('Note', 'super-forms').':</strong> ' . esc_html__( 'Some payment methods require a specific currency to be set for your line items. For instance, when using `ideal` the currency must be set to EUR or Stripe will return an error message.', 'super-forms' )
                                                        ),
                                                        array(
                                                            'name' => 'tax_behavior',
                                                            'title' => esc_html__( 'Tax behavior', 'super-forms' ),
                                                            'subline' => esc_html__( 'Specifies whether the price is considered inclusive of taxes or exclusive of taxes. One of inclusive, exclusive, or unspecified. Once specified as either inclusive or exclusive, it cannot be changed.', 'super-forms' ),
                                                            'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'exclusive' ),
                                                            'accepted_values' => array(array('v'=>'inclusive'), array('v'=>'exclusive'), array('v'=>'unspecified')),
                                                            'type' => 'text',
                                                            'default' => 'unspecified'
                                                        ),
                                                        array(
                                                            'wrap' => false,
                                                            'group' => true,
                                                            'group_name' => 'recurring',
                                                            'vertical' => true,
                                                            'nodes' => array(
                                                                array(
                                                                    'name' => 'interval',
                                                                    'title' => esc_html__( 'Specify billing frequency (defaults to `none` for one-time payments)', 'super-forms' ),
                                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'month' ),
                                                                    'accepted_values' => array(array('v'=>'none'), array('v'=>'day'), array('v'=>'week'), array('v'=>'month'), array('v'=>'year')),
                                                                    'type' => 'text',
                                                                    'default' => 'none'
                                                                ),
                                                                array(
                                                                    'name' => 'interval_count',
                                                                    'title' => esc_html__( 'The number of intervals between subscription billings.', 'super-forms' ),
                                                                    'subline' => esc_html__( 'For example, when billing frequency is set to `month` and interval is set to `3` the customer will be billed every 3 months. Maximum of three years interval allowed (3 years, 36 months, or 156 weeks). Maximum of one year interval allowed (1 year, 12 months, or 52 weeks).', 'super-forms' ),
                                                                    'placeholder' => esc_html__( 'Enter the number of intervals', 'super-forms' ),
                                                                    'type' => 'text',
                                                                    'default' => '1'
                                                                )
                                                            )
                                                        )
                                                    )
                                                ),
                                                array(
                                                    'name' => 'custom_tax_rate',
                                                    'title' => esc_html__( 'Apply custom Tax Rates', 'super-forms' ),
                                                    'accepted_values' => array(array('v'=>'true'), array('v'=>'false')),
                                                    'type' => 'text',
                                                    'default' => 'false'
                                                ),
                                                array(
                                                    'name' => 'tax_rates',
                                                    'title' => esc_html__( 'Tax rates', 'super-forms' ),
                                                    'subline' => sprintf( esc_html__( 'Separate each rate with a comma. You can manage and create Tax Rates in via the Stripe %sDashboard%s.', 'super-forms' ), '<a target="_blank" href="https://dashboard.stripe.com/test/tax-rates">', '</a>' ),
                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'txr_1LmUhbFKn7uROhgCwnWwpN9p' ),
                                                    'type' => 'text',
                                                    'default' => '',
                                                    'filter' => 'custom_tax_rate;true'
                                                )
                                            )
                                        )
                                    )
                                ),
                                array(
                                    'toggle' => true,
                                    'title' => esc_html__( 'Discount settings', 'super-forms' ),
                                    'vertical' => true,
                                    'nodes' => array(
                                        array(
                                            'notice' => 'hint', // hint/info
                                            'content' => sprintf( esc_html__( 'You can create coupons easily via the %scoupon management%s page of the Stripe dashboard.', 'super-forms' ), '<a target="_blank" href="https://dashboard.stripe.com/coupons">', '</a>' )
                                        ),
                                        array(
                                            'wrap' => false,
                                            'group' => true,
                                            'group_name' => 'discounts',
                                            'vertical' => true,
                                            'nodes' => array(
                                                array(
                                                    'name' => 'type',
                                                    'type' => 'radio',
                                                    'options' => array(
                                                        'none' => esc_html__( 'Do not apply any discount', 'super-forms' ),
                                                        'existing_coupon' => esc_html__( 'Based on existing Stripe Coupon ID (recommended)', 'super-forms' ),
                                                        'existing_promotion_code' => esc_html__( 'Based on existing Stripe Promotion ID', 'super-forms' ),
                                                        'new' => esc_html__( 'Create new coupon on the fly', 'super-forms' )
                                                    ),
                                                    'default' => 'none'
                                                ),
                                                array(
                                                    'name' => 'coupon',
                                                    'title' => esc_html__( 'Coupon ID', 'super-forms' ),
                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'SbwGtc0x' ),
                                                    'type' => 'text',
                                                    'default' => '',
                                                    'filter' => 'discounts.type;existing_coupon'
                                                ),
                                                array(
                                                    'name' => 'promotion_code',
                                                    'title' => esc_html__( 'Promotion ID', 'super-forms' ),
                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'SbwGtc0x' ),
                                                    'type' => 'text',
                                                    'default' => '',
                                                    'filter' => 'discounts.type;existing_promotion_code'
                                                ),
                                                array(
                                                    'wrap' => false,
                                                    'group' => true,
                                                    'group_name' => 'new',
                                                    'vertical' => true,
                                                    'filter' => 'discounts.type;new',
                                                    'nodes' => array(
                                                        array(
                                                            'name' => 'name',
                                                            'title' => esc_html__( 'Name of the coupon displayed to customers on, for instance invoices, or receipts. By default the id is shown if name is not set.', 'super-forms' ),
                                                            'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'FALLDISCOUNT' ),
                                                            'type' => 'text',
                                                            'default' => ''
                                                        ),
                                                        array(
                                                            'name' => 'type',
                                                            'type' => 'radio',
                                                            'options' => array(
                                                                'percent_off' => esc_html__( 'Percent Off', 'super-forms' ),
                                                                'amount_off' => esc_html__( 'Amount Off', 'super-forms' )
                                                            ),
                                                            'default' => 'none'
                                                        ),
                                                        array(
                                                            'name' => 'percent_off',
                                                            'title' => esc_html__( 'Value larger tahn 0, and smaller or equal to 100 that represents the discount the coupon will apply.', 'super-forms' ),
                                                            'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), '10' ),
                                                            'type' => 'text',
                                                            'default' => '',
                                                            'filter' => 'new.type;percent_off'
                                                        ),
                                                        array(
                                                            'name' => 'amount_off',
                                                            'title' => esc_html__( 'A positive integer representing the amount to subtract from an invoice total', 'super-forms' ),
                                                            'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), '50.00' ),
                                                            'type' => 'text',
                                                            'default' => '',
                                                            'filter' => 'new.type;amount_off'
                                                        ),
                                                        array(
                                                            'name' => 'currency',
                                                            'title' => sprintf( esc_html__( 'Three-letter ISO currency code. Must be a %ssupported currency%s.', 'super-forms' ), '<a target="_blank" href="https://stripe.com/docs/currencies">', '</a>' ),
                                                            'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'USD' ),
                                                            'type' => 'text',
                                                            'default' => '',
                                                            'filter' => 'new.type;amount_off'
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    )
                                ),
                                array(
                                    'toggle' => true,
                                    'title' => esc_html__( 'Retry payment E-mail settings', 'super-forms' ),
                                    'vertical' => true,
                                    'nodes' => array(
                                        array(
                                            'wrap' => false,
                                            'group' => true,
                                            'group_name' => 'retryPaymentEmail',
                                            'vertical' => true,
                                            'nodes' => array(
                                                array(
                                                    'notice' => 'info', // hint/info
                                                    'content' => '<strong>' . esc_html__('Note', 'super-forms') . ':</strong> ' . sprintf( esc_html__( 'When using any of the following payment methods %s you will want to setup a trigger for event %s to %s, that way you can send the customer an email so that they can retry the payment. You can do so via the [Triggers] tab.', 'super-forms' ), '<code>bacs_debit</code> <code>boleto</code> <code>acss_debit</code> <code>oxxo</code> <code>sepa_debit</code> <code>sofort</code> <code>us_bank_account</code>', '<code>Checkout session async payment failed</code>', '<code>Send an E-mail</code>' )
                                                ),
                                                array(
                                                    'name' => 'expiry',
                                                    'title' => esc_html__( 'Retry payment link expiry in hours', 'super-forms' ),
                                                    'subline' => esc_html__( 'Enter the amount in hours before the retry payment link expires. Must be a number between 0.5 and 24.', 'super-forms' ),
                                                    'accepted_values' => array(array( 'v'=>'0.5', 'i'=>'(expires after 30 min.)'), array( 'v'=>'2', 'i'=>'(expires after 2 hours)'), array( 'v'=>'24', 'i'=>'(expires after 24 hours)')),
                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), '24' ),
                                                    'type' => 'number',
                                                    'min' => 0.5,
                                                    'step' => 0.5,
                                                    'max' => 24,
                                                    'default' => 24
                                                )
                                            )
                                        )
                                    )
                                ),
                                array(
                                    'toggle' => true,
                                    'title' => esc_html__( 'Shipping settings', 'super-forms' ),
                                    'vertical' => true,
                                    'nodes' => array(
                                        array(
                                            'wrap' => false,
                                            'group' => true,
                                            'group_name' => 'shipping_options',
                                            'vertical' => true,
                                            'nodes' => array(
                                                array(
                                                    'name' => 'type',
                                                    'placeholder' => esc_html__( 'e.g. 1', 'super-forms' ),
                                                    'type' => 'radio',
                                                    'options' => array(
                                                        'id' => esc_html__( 'Based on existing Stripe Shipping Rate ID (recommended)', 'super-forms' ),
                                                        'data' => esc_html__( 'Create new shipping rate on the fly', 'super-forms' )
                                                    ),
                                                    'default' => 'none'
                                                ),
                                                array(
                                                    'name' => 'shipping_rate',
                                                    'title' => esc_html__( 'Enter shipping rate ID', 'super-forms' ),
                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'shr_XXXXXXXXXXX' ),
                                                    'type' => 'text',
                                                    'default' => '',
                                                    'filter' => 'shipping_options.type;id'
                                                ),
                                                array(
                                                    'wrap' => false,
                                                    'group' => true,
                                                    'group_name' => 'shipping_rate_data',
                                                    'vertical' => true,
                                                    'filter' => 'shipping_options.type;data',
                                                    'nodes' => array(
                                                        array(
                                                            'name' => 'display_name',
                                                            'title' => esc_html__( 'Display name', 'super-forms' ),
                                                            'subline' => esc_html__( 'The name of the shipping rate, meant to be displayable to the customer. This will appear on CheckoutSessions.', 'super-forms' ),
                                                            'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), esc_html__( 'World wide shipping', 'super-forms' ) ),
                                                            'type' => 'text',
                                                            'default' => ''
                                                        ),
                                                        array(
                                                            'wrap' => false,
                                                            'group' => true,
                                                            'group_name' => 'fixed_amount',
                                                            'vertical' => true,
                                                            'nodes' => array(
                                                                array(
                                                                    'name' => 'amount',
                                                                    'title' => esc_html__( 'Amount to charge for shipping', 'super-forms' ),
                                                                    'subline' => esc_html__( '(must be greater than 0)', 'super-forms' ),
                                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), '6.95' ),
                                                                    'type' => 'text',
                                                                    'default' => ''
                                                                ),
                                                                array(
                                                                    'name' => 'currency',
                                                                    'title' => esc_html__( 'Shipping currency', 'super-forms' ),
                                                                    'subline' => sprintf( esc_html__( 'Three-letter ISO currency code. Must be a %ssupported currency%s.', 'super-forms' ), '<a target="_blank" href="https://stripe.com/docs/currencies">', '</a>' ),
                                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'USD' ),
                                                                    'type' => 'text',
                                                                    'default' => ''
                                                                )
                                                            )
                                                        ),
                                                        array(
                                                            'toggle' => true,
                                                            'title' => esc_html__( 'Tax settings', 'super-forms' ),
                                                            'vertical' => true,
                                                            'nodes' => array(
                                                                array(
                                                                    'name' => 'tax_behavior',
                                                                    'title' => esc_html__( 'Shipping tax behavior', 'super-forms' ),
                                                                    'subline' => esc_html__( 'Specifies whether the rate is considered inclusive of taxes or exclusive of taxes.', 'super-forms' ),
                                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'inclusive' ),
                                                                    'accepted_values' => array(array('v'=>'inclusive'), array('v'=>'exclusive'), array('v'=>'unspecified')),
                                                                    'type' => 'text',
                                                                    'default' => ''
                                                                ),
                                                                array(
                                                                    'name' => 'tax_code',
                                                                    'title' => esc_html__( 'Shipping tax code', 'super-forms' ),
                                                                    'subline' => esc_html__( 'A tax code ID. The Shipping tax code is', 'super-forms' ).': <code>txcd_92010001</code>',
                                                                    'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'txcd_92010001' ),
                                                                    'type' => 'text',
                                                                    'default' => 'txcd_92010001'
                                                                )
                                                            )
                                                        ),
                                                        array(
                                                            'toggle' => true,
                                                            'title' => esc_html__( 'Define estimated shipping time', 'super-forms' ),
                                                            'vertical' => true,
                                                            'nodes' => array(
                                                                array(
                                                                    'notice' => 'hint',
                                                                    'content' => esc_html__( 'Here you can configure the estimated range for how long shipping will take, this will be shown to the customer during checkout.', 'super-forms' ),
                                                                ),
                                                                array(
                                                                    'wrap' => false,
                                                                    'group' => true,
                                                                    'group_name' => 'delivery_estimate',
                                                                    'vertical' => true,
                                                                    'nodes' => array(
                                                                        array(
                                                                            'toggle' => true,
                                                                            'title' => esc_html__( 'The upper bound of the estimated range. If empty, represents no upper bound i.e., infinite.', 'super-forms' ),
                                                                            'vertical' => true,
                                                                            'nodes' => array(
                                                                                array(
                                                                                    'wrap' => false,
                                                                                    'group' => true,
                                                                                    'group_name' => 'maximum',
                                                                                    'inline' => true,
                                                                                    'nodes' => array(
                                                                                        array(
                                                                                            'name' => 'unit',
                                                                                            'title' => esc_html__( 'Unit', 'super-forms' ),
                                                                                            'accepted_values' => array(array('v'=>'hour'), array('v'=>'day'), array('v'=>'business_day'), array('v'=>'week'), array('v'=>'month')),
                                                                                            'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'business_day' ),
                                                                                            'type' => 'text',
                                                                                            'default' => ''
                                                                                        ),
                                                                                        array(
                                                                                            'name' => 'value',
                                                                                            'title' => esc_html__( 'Value', 'super-forms' ),
                                                                                            'subline' => esc_html__( '(must be greater than 0)', 'super-forms' ),
                                                                                            'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), '5' ),
                                                                                            'type' => 'text',
                                                                                            'default' => ''
                                                                                        )
                                                                                    )
                                                                                ),
                                                                            )
                                                                        ),
                                                                        array(
                                                                            'toggle' => true,
                                                                            'title' => esc_html__( 'The lower bound of the estimated range. If empty, represents no lower bound.', 'super-forms' ),
                                                                            'vertical' => true,
                                                                            'nodes' => array(
                                                                                array(
                                                                                    'wrap' => false,
                                                                                    'group' => true,
                                                                                    'group_name' => 'minimum',
                                                                                    'inline' => true,
                                                                                    'nodes' => array(
                                                                                        array(
                                                                                            'name' => 'unit',
                                                                                            'title' => esc_html__( 'Unit', 'super-forms' ),
                                                                                            'accepted_values' => array(array('v'=>'hour'), array('v'=>'day'), array('v'=>'business_day'), array('v'=>'week'), array('v'=>'month')),
                                                                                            'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'business_day' ),
                                                                                            'type' => 'text',
                                                                                            'default' => ''
                                                                                        ),
                                                                                        array(
                                                                                            'name' => 'value',
                                                                                            'title' => esc_html__( 'Value', 'super-forms' ),
                                                                                            'subline' => esc_html__( '(must be greater than 0)', 'super-forms' ),
                                                                                            'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), '5' ),
                                                                                            'type' => 'text',
                                                                                            'default' => ''
                                                                                        )
                                                                                    )
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
                                        )
                                    )
                                ),
                                array(
                                    'toggle' => true,
                                    'title' => esc_html__( 'Payment complete triggers', 'super-forms' ),
                                    'vertical' => true,
                                    'nodes' => array(
                                        array(
                                            'name' => 'update_entry_status',
                                            'title' => esc_html__( 'Entry status after payment completed', 'super-forms' ),
                                            'subline' => esc_html__( '(leave blank to keep the current entry status unchanged)', 'super-forms' ),
                                            'accepted_values' => $entryStatusesValues,
                                            'type' => 'text',
                                            'default' => ''
                                        ),
                                        array(
                                            'wrap' => false,
                                            'group' => true,
                                            'group_name' => 'frontend_posting',
                                            'vertical' => true,
                                            'nodes' => array(
                                                array(
                                                    'name' => 'update_post_status',
                                                    'title' => esc_html__( 'Post status after payment complete', 'super-forms' ),
                                                    'subline' => esc_html__( 'Only used for Front-end posting. Leave blank to keep the current post status unchanged.', 'super-forms' ),
                                                    'accepted_values' => $postStatusesValues,
                                                    'type' => 'text',
                                                    'default' => ''
                                                )
                                            )
                                        ),
                                        array(
                                            'wrap' => false,
                                            'group' => true,
                                            'group_name' => 'register_login',
                                            'vertical' => true,
                                            'nodes' => array(
                                                array(
                                                    'name' => 'update_user_role',
                                                    'title' => esc_html__( 'Change user role after payment complete', 'super-forms' ),
                                                    'subline' => esc_html__( 'Leave blank to keep the current user role unchanged.', 'super-forms' ),
                                                    'accepted_values' => $roleValues,
                                                    'type' => 'text',
                                                    'default' => ''
                                                ),
                                                array(
                                                    'name' => 'update_login_status',
                                                    'title' => esc_html__( 'User login status after payment complete', 'super-forms' ),
                                                    'subline' => esc_html__( 'Only used when registering a new user. You would normally want this to be set to `active` so that the user is able to login. Leave blank to keep the current login status unchanged.', 'super-forms' ),
                                                    'accepted_values' => $userLoginStatusesValues,
                                                    'type' => 'text',
                                                    'default' => ''
                                                )
                                            )
                                        ),
                                    )
                                ),
                                array(
                                    'toggle' => true,
                                    'title' => esc_html__( 'Subscription status update triggers', 'super-forms' ),
                                    'vertical' => true,
                                    'nodes' => array(
                                        array(
                                            'wrap' => false,
                                            'group' => true,
                                            'group_name' => 'subscription',
                                            'vertical' => true,
                                            'nodes' => array(
                                                array(
                                                    'notice' => 'hit', // hint/info
                                                    'content' => sprintf( esc_html__( 'You can add custom Contact Entry statuses via %sSuper Forms > Settings > Backend Settings%s if needed', 'super-forms' ), '<a target="blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#backend-settings') . '">', '</a>' )
                                                ),
                                                array(
                                                    'name' => 'entry_status',
                                                    'type' => 'repeater',
                                                    'inline' => true,
                                                    'nodes' => array(
                                                        array(
                                                            'name' => 'status',
                                                            'title' => esc_html__( 'When subscription status changes to', 'super-forms' ),
                                                            'subline' => esc_html__( '(leave blank to keep the current entry status unchanged)', 'super-forms' ),
                                                            'accepted_values' => array(array('v'=>'active'), array('v'=>'paused'), array('v'=>'canceled')),
                                                            'type' => 'text',
                                                            'default' => ''
                                                        ),
                                                        array(
                                                            'name' => 'value',
                                                            'title' => esc_html__( 'Set entry status to', 'super-forms' ),
                                                            'accepted_values' => $entryStatusesValues,
                                                            'type' => 'text',
                                                            'default' => ''
                                                        ),
                                                    )
                                                ),
                                                array(
                                                    'notice' => 'hint', // hint/info
                                                    'content' => esc_html__( 'Update the created post status when subscription status changes (only required if you use Front-end Posting feature). For forms that do not have the Front-end Posting feature enabled you can define [Triggers] to accomplish a similar behavior.', 'super-forms' ),
                                                ),
                                                array(
                                                    'name' => 'post_status',
                                                    'type' => 'repeater',
                                                    'inline' => true,
                                                    'nodes' => array(
                                                        array(
                                                            'name' => 'status',
                                                            'title' => esc_html__( 'When subscription status changes to', 'super-forms' ),
                                                            'subline' => esc_html__( '(leave blank to keep the current post status unchanged)', 'super-forms' ),
                                                            'accepted_values' => array(array('v'=>'active'), array('v'=>'paused'), array('v'=>'canceled')),
                                                            'type' => 'text',
                                                            'default' => ''
                                                        ),
                                                        array(
                                                            'name' => 'value',
                                                            'title' => esc_html__( 'Set post status to', 'super-forms' ),
                                                            'accepted_values' => $postStatusesValues,
                                                            'type' => 'text',
                                                            'default' => ''
                                                        ),
                                                    )
                                                ),
                                                array(
                                                    'notice' => 'hint', // hint/info
                                                    'content' => esc_html__( 'Update the registered user login status when subscription status changes (only required if this is a paid signup form via Register & Login feature). For none paid signup forms you can define [Triggers] to accomplish a similar behavior.', 'super-forms' ),
                                                ),
                                                array(
                                                    'name' => 'login_status',
                                                    'type' => 'repeater',
                                                    'inline' => true,
                                                    'nodes' => array(
                                                        array(
                                                            'name' => 'status',
                                                            'title' => esc_html__( 'When subscription status changes to', 'super-forms' ),
                                                            'subline' => esc_html__( '(leave blank to keep the login status unchanged)', 'super-forms' ),
                                                            'accepted_values' => array(array('v'=>'active'), array('v'=>'paused'), array('v'=>'canceled')),
                                                            'type' => 'text',
                                                            'default' => ''
                                                        ),
                                                        array(
                                                            'name' => 'value',
                                                            'title' => esc_html__( 'Set login status to', 'super-forms' ),
                                                            'accepted_values' => $userLoginStatusesValues,
                                                            'type' => 'text',
                                                            'default' => ''
                                                        )
                                                    )
                                                ),
                                                array(
                                                    'notice' => 'hint', // hint/info
                                                    'content' => esc_html__( 'Update the registered user role when subscription status changes (only required if this is a paid signup form via Register & Login feature). For none paid signup forms you can define [Triggers] to accomplish a similar behavior.', 'super-forms' ),
                                                ),
                                                array(
                                                    'name' => 'user_role',
                                                    'type' => 'repeater',
                                                    'inline' => true,
                                                    'nodes' => array(
                                                        array(
                                                            'name' => 'status',
                                                            'title' => esc_html__( 'When subscription status changes to', 'super-forms' ),
                                                            'subline' => esc_html__( '(leave blank to keep the current user role unchanged)', 'super-forms' ),
                                                            'accepted_values' => array(array('v'=>'active'), array('v'=>'paused'), array('v'=>'canceled')),
                                                            'type' => 'text',
                                                            'default' => ''
                                                        ),
                                                        array(
                                                            'name' => 'value',
                                                            'title' => esc_html__( 'Set user role to', 'super-forms' ),
                                                            'accepted_values' => $roleValues,
                                                            'type' => 'text',
                                                            'default' => ''
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    )
                                ),
                                array(
                                    'toggle' => true,
                                    'title' => esc_html__( 'Advanced settings', 'super-forms' ),
                                    'vertical' => true,
                                    'nodes' => array(
                                        array(
                                            'name' => 'submit_type',
                                            'title' => esc_html__( 'Submit type', 'super-forms' ),
                                            'subline' => esc_html__( 'Describes the type of transaction being performed by Checkout in order to customize relevant text on the page, such as the submit button.', 'super-forms' ),
                                            'accepted_values' => array(array('v'=>'auto'), array('v'=>'pay'), array('v'=>'book'), array('v'=>'donate')),
                                            'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'donate' ),
                                            'type' => 'text',
                                            'default' => 'auto'
                                        ),
                                        array(
                                            'name' => 'locale',
                                            'title' => esc_html__( 'The IETF language tag of the locale Checkout is displayed in. If blank or auto, the browser’s locale is used.', 'super-forms' ),
                                            'subline' => esc_html__( 'Describes the type of transaction being performed by Checkout in order to customize relevant text on the page, such as the submit button.', 'super-forms' ),
                                            'accepted_values' => array(array('v'=>'auto'), array('v'=>'bg'), array('v'=>'cs'), array('v'=>'da'), array('v'=>'de'), array('v'=>'el'), array('v'=>'en'), array('v'=>'GB'), array('v'=>'es'), array('v'=>'419'), array('v'=>'et'), array('v'=>'fi'), array('v'=>'fil'), array('v'=>'fr'), array('v'=>'CA'), array('v'=>'hr'), array('v'=>'hu'), array('v'=>'id'), array('v'=>'it'), array('v'=>'ja'), array('v'=>'ko'), array('v'=>'lt'), array('v'=>'lv'), array('v'=>'ms'), array('v'=>'mt'), array('v'=>'nb'), array('v'=>'nl'), array('v'=>'pl'), array('v'=>'pt'), array('v'=>'BR'), array('v'=>'ro'), array('v'=>'ru'), array('v'=>'sk'), array('v'=>'sl'), array('v'=>'sv'), array('v'=>'th'), array('v'=>'tr'), array('v'=>'vi'), array('v'=>'zh'), array('v'=>'HK'), array('v'=>'TW')),
                                            'placeholder' => sprintf( esc_html__( 'e.g. %s', 'super-forms' ), 'en' ),
                                            'type' => 'text',
                                            'default' => 'auto'
                                        ),
                                        array(
                                            'name' => 'client_reference_id',
                                            'title' => esc_html__( 'Client reference ID (for developers only)', 'super-forms' ),
                                            'subline' => esc_html__( 'A unique string to reference the Checkout Session. This can be a customer ID, a cart ID, or similar, and can be used to reconcile the session with your internal systems.', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => ''
                                        )
                                    )
                                ),
                                array(
                                    'wrap' => false,
                                    'group' => true,
                                    'vertical' => true,
                                    'nodes' => array(
                                        array(
                                            'toggle' => true,
                                            'title' => esc_html__( 'Translations (raw)', 'super-forms' ),
                                            'notice' => 'hint', // hint/info
                                            'content' => esc_html__( 'Although you can edit existing translated strings below, you may find it easier to use the [Translations] tab instead.', 'super-forms' ),
                                            'nodes' => array(
                                                array(
                                                    'name' => 'i18n',
                                                    'type' => 'textarea',
                                                    'default' => ''
                                                )
                                            )
                                        )
                                    )
                                )
                            ),

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
                        )
                    )
                )
            );
            $prefix = array();
            error_log('loop_over_tab_setting_nodes: '.json_encode($s));
            SUPER_UI::loop_over_tab_setting_nodes($s, $nodes, $prefix);
        }
        public static function add_settings($array, $x){
            $domain = home_url(); // e.g: 'http://domain.com';
            $home_url = trailingslashit($domain);
            $webhookUrl = $home_url . 'sfstripe/webhook'; // super forms stripe webhook e.g: https://domain.com/sfstripe/webhook will be converted into https://domain.com/index.php?sfstripewebhook=true 
            // Stripe Settings
            $array['stripe_checkout'] = array(        
                'hidden' => true,
                'name' => esc_html__( 'Stripe Checkout', 'super-forms' ),
                'label' => esc_html__( 'Stripe Checkout', 'super-forms' ),
                //'html' => array( '<style>.super-settings .stripe-settings-html-notice {}</style>', '<p class="stripe-settings-html-notice">' . sprintf( esc_html__( 'Before filling out these settings we %shighly recommend%s you to read the %sdocumentation%s.', 'super-forms' ), '<strong>', '</strong>', '<a target="_blank" href="https://renstillmann.github.io/super-forms/#/stripe-add-on">', '</a>' ) . '</p>' ),
                'html' => array('<div class="sfui-notice sfui-yellow" style="display:flex;align-items:center;"><p><strong>Note:</strong> You only have to fill out the public key and secret key of the API. Super Forms will automatically create a Webhook with all the required events for you.</p></div>'),
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
                    'stripe_sandbox_webhook_id' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Sandbox webhook ID', 'super-forms' ),
                        'label' => sprintf( esc_html__( '%sCreate a webhook ID%s%sEndpoint URL must be: %s', 'super-forms' ), '<a target="_blank" href="https://dashboard.stripe.com/test/webhooks/">', '</a>', '<br /><br />', '<code>' . $webhookUrl . '</code>' ),
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
                    'stripe_live_webhook_id' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Live webhook ID', 'super-forms' ),
                        'label' => sprintf( esc_html__( '%sCreate a webhook ID%s%sEndpoint URL must be: %s', 'super-forms' ), '<a target="_blank" href="https://dashboard.stripe.com/webhooks/">', '</a>', '<br /><br />', '<code>' . $webhookUrl . '</code>' ),
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
