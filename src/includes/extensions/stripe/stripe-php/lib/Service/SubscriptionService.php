<?php

// File generated from our OpenAPI spec

namespace Stripe\Service;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
/**
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
class SubscriptionService extends \Stripe\Service\AbstractService {

	/**
	 * By default, returns a list of subscriptions that have not been canceled. In
	 * order to list canceled subscriptions, specify <code>status=canceled</code>.
	 *
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Collection<\Stripe\Subscription>
	 */
	public function all( $params = null, $opts = null ) {
		return $this->requestCollection( 'get', '/v1/subscriptions', $params, $opts );
	}

	/**
	 * Cancels a customer’s subscription immediately. The customer will not be charged
	 * again for the subscription.
	 *
	 * Note, however, that any pending invoice items that you’ve created will still be
	 * charged for at the end of the period, unless manually <a
	 * href="#delete_invoiceitem">deleted</a>. If you’ve set the subscription to cancel
	 * at the end of the period, any pending prorations will also be left in place and
	 * collected at the end of the period. But if the subscription is set to cancel
	 * immediately, pending prorations will be removed.
	 *
	 * By default, upon subscription cancellation, Stripe will stop automatic
	 * collection of all finalized invoices for the customer. This is intended to
	 * prevent unexpected payment attempts after the customer has canceled a
	 * subscription. However, you can resume automatic collection of the invoices
	 * manually after subscription cancellation to have us proceed. Or, you could check
	 * for unpaid invoices before allowing the customer to cancel the subscription at
	 * all.
	 *
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Subscription
	 */
	public function cancel( $id, $params = null, $opts = null ) {
		return $this->request( 'delete', $this->buildPath( '/v1/subscriptions/%s', $id ), $params, $opts );
	}

	/**
	 * Creates a new subscription on an existing customer. Each customer can have up to
	 * 500 active or scheduled subscriptions.
	 *
	 * When you create a subscription with
	 * <code>collection_method=charge_automatically</code>, the first invoice is
	 * finalized as part of the request. The <code>payment_behavior</code> parameter
	 * determines the exact behavior of the initial payment.
	 *
	 * To start subscriptions where the first invoice always begins in a
	 * <code>draft</code> status, use <a
	 * href="/docs/billing/subscriptions/subscription-schedules#managing">subscription
	 * schedules</a> instead. Schedules provide the flexibility to model more complex
	 * billing configurations that change over time.
	 *
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Subscription
	 */
	public function create( $params = null, $opts = null ) {
		return $this->request( 'post', '/v1/subscriptions', $params, $opts );
	}

	/**
	 * Removes the currently applied discount on a subscription.
	 *
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Discount
	 */
	public function deleteDiscount( $id, $params = null, $opts = null ) {
		return $this->request( 'delete', $this->buildPath( '/v1/subscriptions/%s/discount', $id ), $params, $opts );
	}

	/**
	 * Initiates resumption of a paused subscription, optionally resetting the billing
	 * cycle anchor and creating prorations. If a resumption invoice is generated, it
	 * must be paid or marked uncollectible before the subscription will be unpaused.
	 * If payment succeeds the subscription will become <code>active</code>, and if
	 * payment fails the subscription will be <code>past_due</code>. The resumption
	 * invoice will void automatically if not paid by the expiration date.
	 *
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Subscription
	 */
	public function resume( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/subscriptions/%s/resume', $id ), $params, $opts );
	}

	/**
	 * Retrieves the subscription with the given ID.
	 *
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Subscription
	 */
	public function retrieve( $id, $params = null, $opts = null ) {
		return $this->request( 'get', $this->buildPath( '/v1/subscriptions/%s', $id ), $params, $opts );
	}

	/**
	 * Search for subscriptions you’ve previously created using Stripe’s <a
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
	 * @return \Stripe\SearchResult<\Stripe\Subscription>
	 */
	public function search( $params = null, $opts = null ) {
		return $this->requestSearchResult( 'get', '/v1/subscriptions/search', $params, $opts );
	}

	/**
	 * Updates an existing subscription to match the specified parameters. When
	 * changing prices or quantities, we optionally prorate the price we charge next
	 * month to make up for any price changes. To preview how the proration is
	 * calculated, use the <a href="/docs/api/invoices/upcoming">upcoming invoice</a>
	 * endpoint.
	 *
	 * By default, we prorate subscription changes. For example, if a customer signs up
	 * on May 1 for a <currency>100</currency> price, they’ll be billed
	 * <currency>100</currency> immediately. If on May 15 they switch to a
	 * <currency>200</currency> price, then on June 1 they’ll be billed
	 * <currency>250</currency> (<currency>200</currency> for a renewal of her
	 * subscription, plus a <currency>50</currency> prorating adjustment for half of
	 * the previous month’s <currency>100</currency> difference). Similarly, a
	 * downgrade generates a credit that is applied to the next invoice. We also
	 * prorate when you make quantity changes.
	 *
	 * Switching prices does not normally change the billing date or generate an
	 * immediate charge unless:
	 *
	 * <ul> <li>The billing interval is changed (for example, from monthly to
	 * yearly).</li> <li>The subscription moves from free to paid, or paid to
	 * free.</li> <li>A trial starts or ends.</li> </ul>
	 *
	 * In these cases, we apply a credit for the unused time on the previous price,
	 * immediately charge the customer using the new price, and reset the billing date.
	 *
	 * If you want to charge for an upgrade immediately, pass
	 * <code>proration_behavior</code> as <code>always_invoice</code> to create
	 * prorations, automatically invoice the customer for those proration adjustments,
	 * and attempt to collect payment. If you pass <code>create_prorations</code>, the
	 * prorations are created but not automatically invoiced. If you want to bill the
	 * customer for the prorations before the subscription’s renewal date, you need to
	 * manually <a href="/docs/api/invoices/create">invoice the customer</a>.
	 *
	 * If you don’t want to prorate, set the <code>proration_behavior</code> option to
	 * <code>none</code>. With this option, the customer is billed
	 * <currency>100</currency> on May 1 and <currency>200</currency> on June 1.
	 * Similarly, if you set <code>proration_behavior</code> to <code>none</code> when
	 * switching between different billing intervals (for example, from monthly to
	 * yearly), we don’t generate any credits for the old subscription’s unused time.
	 * We still reset the billing date and bill immediately for the new subscription.
	 *
	 * Updating the quantity on a subscription many times in an hour may result in <a
	 * href="/docs/rate-limits">rate limiting</a>. If you need to bill for a frequently
	 * changing quantity, consider integrating <a
	 * href="/docs/billing/subscriptions/usage-based">usage-based billing</a> instead.
	 *
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Subscription
	 */
	public function update( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/subscriptions/%s', $id ), $params, $opts );
	}
}
