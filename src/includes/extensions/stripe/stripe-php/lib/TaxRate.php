<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * Tax rates can be applied to <a href="https://stripe.com/docs/billing/invoices/tax-rates">invoices</a>, <a href="https://stripe.com/docs/billing/subscriptions/taxes">subscriptions</a> and <a href="https://stripe.com/docs/payments/checkout/set-up-a-subscription#tax-rates">Checkout Sessions</a> to collect tax.
 *
 * Related guide: <a href="https://stripe.com/docs/billing/taxes/tax-rates">Tax rates</a>
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property bool $active Defaults to <code>true</code>. When set to <code>false</code>, this tax rate cannot be used with new applications or Checkout Sessions, but will still work for subscriptions and invoices that already have it set.
 * @property null|string $country Two-letter country code (<a href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2">ISO 3166-1 alpha-2</a>).
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|string $description An arbitrary string attached to the tax rate for your internal use only. It will not be visible to your customers.
 * @property string $display_name The display name of the tax rates as it will appear to your customer on their receipt email, PDF, and the hosted invoice page.
 * @property null|float $effective_percentage Actual/effective tax rate percentage out of 100. For tax calculations with automatic_tax[enabled]=true, this percentage reflects the rate actually used to calculate tax based on the product's taxability and whether the user is registered to collect taxes in the corresponding jurisdiction.
 * @property bool $inclusive This specifies if the tax rate is inclusive or exclusive.
 * @property null|string $jurisdiction The jurisdiction for the tax rate. You can use this label field for tax reporting purposes. It also appears on your customer’s invoice.
 * @property null|string $jurisdiction_level The level of the jurisdiction that imposes this tax rate. Will be <code>null</code> for manually defined tax rates.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|\Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property float $percentage Tax rate percentage out of 100. For tax calculations with automatic_tax[enabled]=true, this percentage includes the statutory tax rate of non-taxable jurisdictions.
 * @property null|string $state <a href="https://en.wikipedia.org/wiki/ISO_3166-2:US">ISO 3166-2 subdivision code</a>, without country prefix. For example, &quot;NY&quot; for New York, United States.
 * @property null|string $tax_type The high-level tax type, such as <code>vat</code> or <code>sales_tax</code>.
 */
class TaxRate extends ApiResource {

	const OBJECT_NAME = 'tax_rate';

	use ApiOperations\All;
	use ApiOperations\Create;
	use ApiOperations\Retrieve;
	use ApiOperations\Update;

	const JURISDICTION_LEVEL_CITY     = 'city';
	const JURISDICTION_LEVEL_COUNTRY  = 'country';
	const JURISDICTION_LEVEL_COUNTY   = 'county';
	const JURISDICTION_LEVEL_DISTRICT = 'district';
	const JURISDICTION_LEVEL_MULTIPLE = 'multiple';
	const JURISDICTION_LEVEL_STATE    = 'state';

	const TAX_TYPE_AMUSEMENT_TAX      = 'amusement_tax';
	const TAX_TYPE_COMMUNICATIONS_TAX = 'communications_tax';
	const TAX_TYPE_GST                = 'gst';
	const TAX_TYPE_HST                = 'hst';
	const TAX_TYPE_IGST               = 'igst';
	const TAX_TYPE_JCT                = 'jct';
	const TAX_TYPE_LEASE_TAX          = 'lease_tax';
	const TAX_TYPE_PST                = 'pst';
	const TAX_TYPE_QST                = 'qst';
	const TAX_TYPE_RST                = 'rst';
	const TAX_TYPE_SALES_TAX          = 'sales_tax';
	const TAX_TYPE_VAT                = 'vat';
}
