<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $amount The amount, in cents (or local equivalent).
 * @property null|int $amount_excluding_tax The integer amount in cents (or local equivalent) representing the amount for this line item, excluding all tax and discounts.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property null|string $description An arbitrary string attached to the object. Often useful for displaying to users.
 * @property null|\Stripe\StripeObject[] $discount_amounts The amount of discount calculated per discount for this line item.
 * @property bool $discountable If true, discounts will apply to this line item. Always false for prorations.
 * @property (string|\Stripe\Discount)[] $discounts The discounts applied to the invoice line item. Line item discounts are applied before invoice discounts. Use <code>expand[]=discounts</code> to expand each discount.
 * @property null|string $invoice The ID of the invoice that contains this line item.
 * @property null|string|\Stripe\InvoiceItem $invoice_item The ID of the <a href="https://stripe.com/docs/api/invoiceitems">invoice item</a> associated with this line item if any.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format. Note that for line items with <code>type=subscription</code> this will reflect the metadata of the subscription that caused the line item to be created.
 * @property \Stripe\StripeObject $period
 * @property null|\Stripe\Plan $plan The plan of the subscription, if the line item is a subscription or a proration.
 * @property null|\Stripe\Price $price The price of the line item.
 * @property bool $proration Whether this is a proration.
 * @property null|\Stripe\StripeObject $proration_details Additional details for proration line items
 * @property null|int $quantity The quantity of the subscription, if the line item is a subscription or a proration.
 * @property null|string|\Stripe\Subscription $subscription The subscription that the invoice item pertains to, if any.
 * @property null|string|\Stripe\SubscriptionItem $subscription_item The subscription item that generated this line item. Left empty if the line item is not an explicit result of a subscription.
 * @property null|\Stripe\StripeObject[] $tax_amounts The amount of tax calculated per tax rate for this line item
 * @property null|\Stripe\TaxRate[] $tax_rates The tax rates which apply to the line item.
 * @property string $type A string identifying the type of the source of this line item, either an <code>invoiceitem</code> or a <code>subscription</code>.
 * @property null|string $unit_amount_excluding_tax The amount in cents (or local equivalent) representing the unit amount for this line item, excluding all tax and discounts.
 */
class InvoiceLineItem extends ApiResource {

	const OBJECT_NAME = 'line_item';

	use ApiOperations\Update;
}
