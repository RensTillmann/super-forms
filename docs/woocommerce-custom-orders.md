# WooCommerce Custom Orders Add-on

Create orders on the fly without the need to use the normal workflow WooCommerce uses and with much flexibility. In comparison to the [WooCommerce Checkout](woocommerce-checkout) feature it allows you to completely skip the Cart and/or Checkout page.

* [Features & Options](#features-options)
* [Installing demo form](#installing-demo-form)

## Features & Options

?> **Note:** all settings are compatible with [{tags} system](tags-system)

* Create orders
* Update existing orders
* Allow for Guest checkout
* Option to load an existing/previous order
* Option to search/append a customer to an order
* Option to alter customer address after customer was appended (this will not update the actual customer under "Users" in WordPress)
* Option to add additional shipping costs dynamically
* Abillity to create a custom overview/summary with a [HTML element](html) in combination with [foreach loops](email-foreach-loops)
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

## Installing Demo Form

Even though you can easily start building your form from scratch it is adviced to check out the demo forms for this add-on.
You can "One-click" install them from the menu `Super Forms` > `Demos`.
Once installed you can see how everything works and change/alter elements and settings where needed :)
