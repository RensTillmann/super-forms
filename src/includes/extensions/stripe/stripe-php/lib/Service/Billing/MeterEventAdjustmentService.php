<?php

// File generated from our OpenAPI spec

namespace Stripe\Service\Billing;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
/**
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
class MeterEventAdjustmentService extends \Stripe\Service\AbstractService {

	/**
	 * Creates a billing meter event adjustment.
	 *
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Billing\MeterEventAdjustment
	 */
	public function create( $params = null, $opts = null ) {
		return $this->request( 'post', '/v1/billing/meter_event_adjustments', $params, $opts );
	}
}
