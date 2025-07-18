<?php

// File generated from our OpenAPI spec

namespace Stripe\Service;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
/**
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
class InvoiceService extends \Stripe\Service\AbstractService {

	/**
	 * You can list all invoices, or list the invoices for a specific customer. The
	 * invoices are returned sorted by creation date, with the most recently created
	 * invoices appearing first.
	 *
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Collection<\Stripe\Invoice>
	 */
	public function all( $params = null, $opts = null ) {
		return $this->requestCollection( 'get', '/v1/invoices', $params, $opts );
	}

	/**
	 * When retrieving an invoice, you’ll get a <strong>lines</strong> property
	 * containing the total count of line items and the first handful of those items.
	 * There is also a URL where you can retrieve the full (paginated) list of line
	 * items.
	 *
	 * @param string                                               $parentId
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Collection<\Stripe\InvoiceLineItem>
	 */
	public function allLines( $parentId, $params = null, $opts = null ) {
		return $this->requestCollection( 'get', $this->buildPath( '/v1/invoices/%s/lines', $parentId ), $params, $opts );
	}

	/**
	 * This endpoint creates a draft invoice for a given customer. The invoice remains
	 * a draft until you <a href="#finalize_invoice">finalize</a> the invoice, which
	 * allows you to <a href="#pay_invoice">pay</a> or <a href="#send_invoice">send</a>
	 * the invoice to your customers.
	 *
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Invoice
	 */
	public function create( $params = null, $opts = null ) {
		return $this->request( 'post', '/v1/invoices', $params, $opts );
	}

	/**
	 * Permanently deletes a one-off invoice draft. This cannot be undone. Attempts to
	 * delete invoices that are no longer in a draft state will fail; once an invoice
	 * has been finalized or if an invoice is for a subscription, it must be <a
	 * href="#void_invoice">voided</a>.
	 *
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Invoice
	 */
	public function delete( $id, $params = null, $opts = null ) {
		return $this->request( 'delete', $this->buildPath( '/v1/invoices/%s', $id ), $params, $opts );
	}

	/**
	 * Stripe automatically finalizes drafts before sending and attempting payment on
	 * invoices. However, if you’d like to finalize a draft invoice manually, you can
	 * do so using this method.
	 *
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Invoice
	 */
	public function finalizeInvoice( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/invoices/%s/finalize', $id ), $params, $opts );
	}

	/**
	 * Marking an invoice as uncollectible is useful for keeping track of bad debts
	 * that can be written off for accounting purposes.
	 *
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Invoice
	 */
	public function markUncollectible( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/invoices/%s/mark_uncollectible', $id ), $params, $opts );
	}

	/**
	 * Stripe automatically creates and then attempts to collect payment on invoices
	 * for customers on subscriptions according to your <a
	 * href="https://dashboard.stripe.com/account/billing/automatic">subscriptions
	 * settings</a>. However, if you’d like to attempt payment on an invoice out of the
	 * normal collection schedule or for some other reason, you can do so.
	 *
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Invoice
	 */
	public function pay( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/invoices/%s/pay', $id ), $params, $opts );
	}

	/**
	 * Retrieves the invoice with the given ID.
	 *
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Invoice
	 */
	public function retrieve( $id, $params = null, $opts = null ) {
		return $this->request( 'get', $this->buildPath( '/v1/invoices/%s', $id ), $params, $opts );
	}

	/**
	 * Search for invoices you’ve previously created using Stripe’s <a
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
	 * @return \Stripe\SearchResult<\Stripe\Invoice>
	 */
	public function search( $params = null, $opts = null ) {
		return $this->requestSearchResult( 'get', '/v1/invoices/search', $params, $opts );
	}

	/**
	 * Stripe will automatically send invoices to customers according to your <a
	 * href="https://dashboard.stripe.com/account/billing/automatic">subscriptions
	 * settings</a>. However, if you’d like to manually send an invoice to your
	 * customer out of the normal schedule, you can do so. When sending invoices that
	 * have already been paid, there will be no reference to the payment in the email.
	 *
	 * Requests made in test-mode result in no emails being sent, despite sending an
	 * <code>invoice.sent</code> event.
	 *
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Invoice
	 */
	public function sendInvoice( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/invoices/%s/send', $id ), $params, $opts );
	}

	/**
	 * At any time, you can preview the upcoming invoice for a customer. This will show
	 * you all the charges that are pending, including subscription renewal charges,
	 * invoice item charges, etc. It will also show you any discounts that are
	 * applicable to the invoice.
	 *
	 * Note that when you are viewing an upcoming invoice, you are simply viewing a
	 * preview – the invoice has not yet been created. As such, the upcoming invoice
	 * will not show up in invoice listing calls, and you cannot use the API to pay or
	 * edit the invoice. If you want to change the amount that your customer will be
	 * billed, you can add, remove, or update pending invoice items, or update the
	 * customer’s discount.
	 *
	 * You can preview the effects of updating a subscription, including a preview of
	 * what proration will take place. To ensure that the actual proration is
	 * calculated exactly the same as the previewed proration, you should pass the
	 * <code>subscription_proration_date</code> parameter when doing the actual
	 * subscription update. The recommended way to get only the prorations being
	 * previewed is to consider only proration line items where
	 * <code>period[start]</code> is equal to the
	 * <code>subscription_proration_date</code> value passed in the request.
	 *
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Invoice
	 */
	public function upcoming( $params = null, $opts = null ) {
		return $this->request( 'get', '/v1/invoices/upcoming', $params, $opts );
	}

	/**
	 * When retrieving an upcoming invoice, you’ll get a <strong>lines</strong>
	 * property containing the total count of line items and the first handful of those
	 * items. There is also a URL where you can retrieve the full (paginated) list of
	 * line items.
	 *
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Collection<\Stripe\InvoiceLineItem>
	 */
	public function upcomingLines( $params = null, $opts = null ) {
		return $this->requestCollection( 'get', '/v1/invoices/upcoming/lines', $params, $opts );
	}

	/**
	 * Draft invoices are fully editable. Once an invoice is <a
	 * href="/docs/billing/invoices/workflow#finalized">finalized</a>, monetary values,
	 * as well as <code>collection_method</code>, become uneditable.
	 *
	 * If you would like to stop the Stripe Billing engine from automatically
	 * finalizing, reattempting payments on, sending reminders for, or <a
	 * href="/docs/billing/invoices/reconciliation">automatically reconciling</a>
	 * invoices, pass <code>auto_advance=false</code>.
	 *
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Invoice
	 */
	public function update( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/invoices/%s', $id ), $params, $opts );
	}

	/**
	 * Updates an invoice’s line item. Some fields, such as <code>tax_amounts</code>,
	 * only live on the invoice line item, so they can only be updated through this
	 * endpoint. Other fields, such as <code>amount</code>, live on both the invoice
	 * item and the invoice line item, so updates on this endpoint will propagate to
	 * the invoice item as well. Updating an invoice’s line item is only possible
	 * before the invoice is finalized.
	 *
	 * @param string                                               $parentId
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\InvoiceLineItem
	 */
	public function updateLine( $parentId, $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/invoices/%s/lines/%s', $parentId, $id ), $params, $opts );
	}

	/**
	 * Mark a finalized invoice as void. This cannot be undone. Voiding an invoice is
	 * similar to <a href="#delete_invoice">deletion</a>, however it only applies to
	 * finalized invoices and maintains a papertrail where the invoice can still be
	 * found.
	 *
	 * Consult with local regulations to determine whether and how an invoice might be
	 * amended, canceled, or voided in the jurisdiction you’re doing business in. You
	 * might need to <a href="#create_invoice">issue another invoice</a> or <a
	 * href="#create_credit_note">credit note</a> instead. Stripe recommends that you
	 * consult with your legal counsel for advice specific to your business.
	 *
	 * @param string                                               $id
	 * @param null|array                                           $params
	 * @param null|RequestOptionsArray|\Stripe\Util\RequestOptions $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Invoice
	 */
	public function voidInvoice( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/invoices/%s/void', $id ), $params, $opts );
	}
}
