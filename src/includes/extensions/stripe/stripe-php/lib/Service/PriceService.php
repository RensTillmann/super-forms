<?php

// File generated from our OpenAPI spec

namespace Stripe\Service;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
/**
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
class PriceService extends \Stripe\Service\AbstractService {

	/**
	 * Returns a list of your active prices, excluding <a
	 * href="/docs/products-prices/pricing-models#inline-pricing">inline prices</a>.
	 * For the list of inactive prices, set <code>active</code> to false.
	 *
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Collection<\Stripe\Price>
	 */
	public function all( $params = null, $opts = null ) {
		return $this->requestCollection( 'get', '/v1/prices', $params, $opts );
	}

	/**
	 * Creates a new price for an existing product. The price can be recurring or
	 * one-time.
	 *
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Price
	 */
	public function create( $params = null, $opts = null ) {
		return $this->request( 'post', '/v1/prices', $params, $opts );
	}

	/**
	 * Retrieves the price with the given ID.
	 *
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Price
	 */
	public function retrieve( $id, $params = null, $opts = null ) {
		return $this->request( 'get', $this->buildPath( '/v1/prices/%s', $id ), $params, $opts );
	}

	/**
	 * Search for prices you’ve previously created using Stripe’s <a
	 * href="/docs/search#search-query-language">Search Query Language</a>. Don’t use
	 * search in read-after-write flows where strict consistency is necessary. Under
	 * normal operating conditions, data is searchable in less than a minute.
	 * Occasionally, propagation of new or updated data can be up to an hour behind
	 * during outages. Search functionality is not available to merchants in India.
	 *
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\SearchResult<\Stripe\Price>
	 */
	public function search( $params = null, $opts = null ) {
		return $this->requestSearchResult( 'get', '/v1/prices/search', $params, $opts );
	}

	/**
	 * Updates the specified price by setting the values of the parameters passed. Any
	 * parameters not provided are left unchanged.
	 *
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Price
	 */
	public function update( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/prices/%s', $id ), $params, $opts );
	}
}
