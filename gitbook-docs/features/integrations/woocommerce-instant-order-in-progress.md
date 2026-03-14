---
description: >-
  Create WooCommerce orders on the fly with a single form submission. Skipping
  the cart and checkout pages and providing a "payment link" after form
  submission.
---

# WooCommerce Instant Order (in progress)

{% hint style="danger" %}
This feature is work in progress and not yet available to the public.
{% endhint %}

Create orders on the fly without the need to use the normal workflow WooCommerce uses and with much flexibility. In comparison to the [**WooCommerce Checkout**](woocommerce-checkout/) feature it allows you to completely skip the Cart and Checkout pages.

## Features & Options

{% hint style="info" %}
**Note:** all settings are compatible with {tags} system
{% endhint %}

* Create orders
* Update existing orders
* Allow for Guest checkout
* Option to load an existing/previous order
* Option to search/append a customer to an order
* Option to alter customer address after customer was appended (this will not update the actual customer under "Users" in WordPress)
* Option to add additional shipping costs dynamically
* Ability to create a custom overview/summary with a HTML element in combination with foreach loops
* Custom redirect after order is placed/created
  * Payment gateway (default)
  * Pay for order page (redirects to front-end payment page)
  * Created order (redirects to order in back-end)
  * Order received page (redirects to front-end summary page)
  * Disabled (do not redirect)
* Create orders with either a single product or multiple products with the following possible options
  * Example with tags: `{product_id}|{quantity}|{name}|{variation_id}|{subtotal}|{total}|{tax_class}|{variation}`
  * Example without tags: `Example: 0|1|T-shirt|0|10|10|0|color;red#size;XL`
* Add custom meta data for products e.g:
  * Example with tags: `{id}|Color|{color}`
  * Example without tags: `82921|Color|Red`
* Add shipping costs e.g:
  * Example with tags: `{shipping_method_id}|{shipping_method_label}|{cost}|{shipping_method}`
  * Example without tags: `flat_rate_shipping|Ship by airplane|275|flat_rate`
* Add order fee(s)
  * Example with tags: `{fee_name}|{amount}|zero-rate|taxable`
  * Example without tags: `Extra processing fee|45|zero-rate|taxable`
* Option to save coupon code
* Option to save customer note
* Option to set order status manually
* Option to save order notes
* Option to set a fixed payment gateway/method
* Option to override Customer ID (defaults to logged in user)
* Option to save custom order meta data e.g:
  * `meta_key|{field1}`
* Define billing and shipping address manually via field mapping e.g:

```
first_name|{first_name}
last_name|{last_name}
company|{company}
email|{email}
phone|{phone}
address_1|{address_1}
address_2|{address_2}
city|{city}
state|{state}
postcode|{postcode}
country|{country}
```
