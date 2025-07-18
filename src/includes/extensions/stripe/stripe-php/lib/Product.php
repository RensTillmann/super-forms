<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * Products describe the specific goods or services you offer to your customers.
 * For example, you might offer a Standard and Premium version of your goods or service; each version would be a separate Product.
 * They can be used in conjunction with <a href="https://stripe.com/docs/api#prices">Prices</a> to configure pricing in Payment Links, Checkout, and Subscriptions.
 *
 * Related guides: <a href="https://stripe.com/docs/billing/subscriptions/set-up-subscription">Set up a subscription</a>,
 * <a href="https://stripe.com/docs/payment-links">share a Payment Link</a>,
 * <a href="https://stripe.com/docs/payments/accept-a-payment#create-product-prices-upfront">accept payments with Checkout</a>,
 * and more about <a href="https://stripe.com/docs/products-prices/overview">Products and Prices</a>
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property bool $active Whether the product is currently available for purchase.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|string|\Stripe\Price $default_price The ID of the <a href="https://stripe.com/docs/api/prices">Price</a> object that is the default price for this product.
 * @property null|string $description The product's description, meant to be displayable to the customer. Use this field to optionally store a long form explanation of the product being sold for your own rendering purposes.
 * @property string[] $images A list of up to 8 URLs of images for this product, meant to be displayable to the customer.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject[] $marketing_features A list of up to 15 marketing features for this product. These are displayed in <a href="https://stripe.com/docs/payments/checkout/pricing-table">pricing tables</a>.
 * @property \Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property string $name The product's name, meant to be displayable to the customer.
 * @property null|\Stripe\StripeObject $package_dimensions The dimensions of this product for shipping purposes.
 * @property null|bool $shippable Whether this product is shipped (i.e., physical goods).
 * @property null|string $statement_descriptor Extra information about a product which will appear on your customer's credit card statement. In the case that multiple products are billed at once, the first statement descriptor will be used.
 * @property null|string|\Stripe\TaxCode $tax_code A <a href="https://stripe.com/docs/tax/tax-categories">tax code</a> ID.
 * @property string $type The type of the product. The product is either of type <code>good</code>, which is eligible for use with Orders and SKUs, or <code>service</code>, which is eligible for use with Subscriptions and Plans.
 * @property null|string $unit_label A label that represents units of this product. When set, this will be included in customers' receipts, invoices, Checkout, and the customer portal.
 * @property int $updated Time at which the object was last updated. Measured in seconds since the Unix epoch.
 * @property null|string $url A URL of a publicly-accessible webpage for this product.
 */
class Product extends ApiResource {

	const OBJECT_NAME = 'product';

	use ApiOperations\All;
	use ApiOperations\Create;
	use ApiOperations\Delete;
	use ApiOperations\NestedResource;
	use ApiOperations\Retrieve;
	use ApiOperations\Search;
	use ApiOperations\Update;

	const TYPE_GOOD    = 'good';
	const TYPE_SERVICE = 'service';

	/**
	 * @param null|array        $params
	 * @param null|array|string $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\SearchResult<\Stripe\Product> the product search results
	 */
	public static function search( $params = null, $opts = null ) {
		$url = '/v1/products/search';

		return static::_requestPage( $url, \Stripe\SearchResult::class, $params, $opts );
	}

	const PATH_FEATURES = '/features';

	/**
	 * @param string            $id the ID of the product on which to retrieve the product features
	 * @param null|array        $params
	 * @param null|array|string $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\Collection<\Stripe\ProductFeature> the list of product features
	 */
	public static function allFeatures( $id, $params = null, $opts = null ) {
		return self::_allNestedResources( $id, static::PATH_FEATURES, $params, $opts );
	}

	/**
	 * @param string            $id the ID of the product on which to create the product feature
	 * @param null|array        $params
	 * @param null|array|string $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\ProductFeature
	 */
	public static function createFeature( $id, $params = null, $opts = null ) {
		return self::_createNestedResource( $id, static::PATH_FEATURES, $params, $opts );
	}

	/**
	 * @param string            $id the ID of the product to which the product feature belongs
	 * @param string            $featureId the ID of the product feature to delete
	 * @param null|array        $params
	 * @param null|array|string $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\ProductFeature
	 */
	public static function deleteFeature( $id, $featureId, $params = null, $opts = null ) {
		return self::_deleteNestedResource( $id, static::PATH_FEATURES, $featureId, $params, $opts );
	}

	/**
	 * @param string            $id the ID of the product to which the product feature belongs
	 * @param string            $featureId the ID of the product feature to retrieve
	 * @param null|array        $params
	 * @param null|array|string $opts
	 *
	 * @throws \Stripe\Exception\ApiErrorException if the request fails
	 *
	 * @return \Stripe\ProductFeature
	 */
	public static function retrieveFeature( $id, $featureId, $params = null, $opts = null ) {
		return self::_retrieveNestedResource( $id, static::PATH_FEATURES, $featureId, $params, $opts );
	}
}
