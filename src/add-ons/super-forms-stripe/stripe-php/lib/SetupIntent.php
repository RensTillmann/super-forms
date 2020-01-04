<?php

namespace Stripe;

/**
 * Class SetupIntent
 *
 * @property string $id
 * @property string $object
 * @property string|null $application
 * @property string|null $cancellation_reason
 * @property string|null $client_secret
 * @property int $created
 * @property string|null $customer
 * @property string|null $description
 * @property mixed|null $last_setup_error
 * @property bool $livemode
 * @property string|null $mandate
 * @property \Stripe\StripeObject $metadata
 * @property mixed|null $next_action
 * @property string|null $on_behalf_of
 * @property string|null $payment_method
 * @property mixed|null $payment_method_options
 * @property string[] $payment_method_types
 * @property string|null $single_use_mandate
 * @property string $status
 * @property string $usage
 *
 * @package Stripe
 */
class SetupIntent extends ApiResource
{
    const OBJECT_NAME = 'setup_intent';

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    /**
     * These constants are possible representations of the status field.
     *
     * @link https://stripe.com/docs/api/setup_intents/object#setup_intent_object-status
     */
    const STATUS_CANCELED                = 'canceled';
    const STATUS_PROCESSING              = 'processing';
    const STATUS_REQUIRES_ACTION         = 'requires_action';
    const STATUS_REQUIRES_CONFIRMATION   = 'requires_confirmation';
    const STATUS_REQUIRES_PAYMENT_METHOD = 'requires_payment_method';
    const STATUS_SUCCEEDED               = 'succeeded';

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return SetupIntent The canceled setup intent.
     */
    public function cancel($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/cancel';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return SetupIntent The confirmed setup intent.
     */
    public function confirm($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/confirm';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }
}
