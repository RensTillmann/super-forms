<?php

// File generated from our OpenAPI spec

namespace Stripe\Service\Issuing;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
/**
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
class PhysicalBundleService extends \Stripe\Service\AbstractService {

	/**
	 * Returns a list of physical bundle objects. The objects are sorted in descending
	 * order by creation date, with the most recently created object appearing first.
	 *
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Collection<\Stripe\Issuing\PhysicalBundle>
	 */
	public function all( $params = null, $opts = null ) {
		return $this->requestCollection( 'get', '/v1/issuing/physical_bundles', $params, $opts );
	}

	/**
	 * Retrieves a physical bundle object.
	 *
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Issuing\PhysicalBundle
	 */
	public function retrieve( $id, $params = null, $opts = null ) {
		return $this->request( 'get', $this->buildPath( '/v1/issuing/physical_bundles/%s', $id ), $params, $opts );
	}
}
