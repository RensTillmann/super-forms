<?php
require_once( 'stripe-php/init.php' );
header('Content-Type: application/json');
define( 'DOING_AJAX', true );
define( 'SHORTINIT', true );
$dir = '../../../../../wp-load.php';
if(!file_exists($dir)){
    $dir = '../../../wp-load.php';
}
require_once($dir);
$settings = get_option( 'super_settings' );
if(empty($settings['stripe_secret_key'])){
    $settings['stripe_secret_key'] = 'sk_test_UplKLJyKZpdfl0Emz9VglXru';
}

\Stripe\Stripe::setApiKey($settings['stripe_secret_key']);
header('Content-Type: application/json');
# retrieve json from POST body
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);

$intent = null;
try {
    if (isset($json_obj->payment_method_id)) {
        # Create the PaymentIntent
        $intent = \Stripe\PaymentIntent::create([
            'payment_method' => $json_obj->payment_method_id,
            'amount' => 1099,
            'currency' => 'eur',
            'confirmation_method' => 'manual',
            'confirm' => true,

            // application_fee_amount
            // capture_method
            // confirmation_method
            // customer
            // description
            // metadata
            // on_behalf_of
            // payment_method
            // payment_method_types
            // receipt_email
            // save_payment_method
            // shipping
            // statement_descriptor
            // transfer_data
            // transfer_group

        ]);
    }
    if (isset($json_obj->payment_intent_id)) {
        $intent = \Stripe\PaymentIntent::retrieve(
            $json_obj->payment_intent_id
        );
        $intent->confirm();
    }
    generatePaymentResponse($intent);
} catch (\Stripe\Error\Base $e) {
    # Display error on client
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

function generatePaymentResponse($intent) {
    if ($intent->status == 'requires_source_action' && $intent->next_action->type == 'use_stripe_sdk') {
        # Tell the client to handle the action
        echo json_encode([
            'requires_action' => true,
            'payment_intent_client_secret' => $intent->client_secret
        ]);
    } else if ($intent->status == 'succeeded') {
        # The payment didn’t need any additional actions and completed!
        # Handle post-payment fulfillment
        echo json_encode([
        "success" => true
        ]);
    } else {
        # Invalid status
        http_response_code(500);
        echo json_encode(['error' => 'Invalid PaymentIntent status']);
    }
}