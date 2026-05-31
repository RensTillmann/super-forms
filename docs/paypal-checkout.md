# PayPal Checkout

## Table of Contents

* [Key Features](#key-features)
* [Example Forms](#example-forms)
* [Setup Instructions](#setup-instructions)
  * [Introduction and Requirements](#introduction-and-requirements)
  * [Step 1 – Installation / Activation of plugin](#step-1-installation-activation-of-plugin)
  * [Step 2 – Enabling paypal checkout](#step-2-enabling-paypal-checkout)
  * [Step 3 – Adding merchant email to receive payments](#step-3-adding-merchant-email-toreceive-payments)
  * [Step 4 – Choosing your currency](#step-4-choosing-your-currency)
  * [Step 5 – Shipping address requirement setting](#step-5-shipping-address-requirement-setting)
  * [Step 6 – Choosing the payment method](#step-6-choosing-the-payment-method)
    * [Single product or service checkout](#_single-product-or-service-checkout)
    * [Donation checkout](#donation-checkout)
    * [Subscription checkout](#subscription-checkout)
    * [Cart checkout (for multiple product checkout)](#cart-checkout-for-multiple-product-checkout)
  * [Step 7 – Setting up return URL](#step-7-setting-up-return-url)
  * [Step 8 – Setting up cancel URL](#step-8-setting-up-cancel-url)
  * [Step 9 – Sending email after payment completed](#step-9-sending-e-mail-after-payment-completed)
  * [Step 10 – Testing with sandbox account before going live](#step-10-testing-with-sandbox-account-before-going-live)

### Key Features

**In short this gives you the following features:**

* Payment methods
  * Single product or service
  * Donations
  * Subscriptions
  * Cart checkout (for multiple products)
    * Option to define tax, weight, discount amount, discount rate, handling fee
* Send E-mail after payment completed
  * Optionally add attachments
* Update post status after payment completed (only for [Front-end Posting](front-end-posting.md))
* Registered user login status after payment complete (only for [Register &amp; Login](register-login.md))
* Change user role after payment complete (only for [Register &amp; Login](register-login.md))
* Conditionally checkout to PayPal based on conditional logic
* Change Contact Entry status after payment was completed
* Prompt buyers for a shipping
  * Prompt for an address, but do not require one
  * Do not prompt for an address
  * Prompt for an address and require one
* Custom return URL _User will be redirected to this URL after making a payment_
* Custom cancel URL _User that cancels payment will be redirected to this URL_
* Allow buyers to specify the quantity
* Select weight unit (lbs/kgs)
* Other options to define:
  * Define PayPal notify URL (IPN)
  * Define currency
  * Define language for the billing information/log-in page
  * Define Handling charges
  * Define Invoice number
  * Define the area code for U.S. phone numbers, or the country code for phone numbers outside the U.S.
  * Define the three-digit prefix for U.S. phone numbers, or the entire phone number for phone numbers outside the U.S., excluding country code.
  * Define the four-digit phone number for U.S. phone numbers.
  * Parse address to PayPal based on form input data
    * Option to override the PayPal member's stored address

**Compatible with:**

* [Calculator element](calculator.md)
* [Front-end Posting](front-end-posting.md)
* [Register &amp; Login](register-login.md)

### Example Forms

Example forms can be found under `Super Forms > Demos` within your wordpress dashboard.

### Setup Instructions

#### Step 1 – Installation / Activation of plugin

Make sure you have installed and activated the plugin. If you do not know how to do this please [watch the tutorial video](http://f4d.nl/super-forms/knowledge-base-article/upload-install/).

#### Step 2 – Enabling paypal checkout

Go to `Super Forms > Create Form` (or edit an existing form of your choosing).
Click on `Form Settings` TAB on the right hand side to open up the Settings panel.
From the **dropdown menu** choose `PayPal Checkout` (this will open all the settings related to the PayPal checkout process).
Make sure to Enable the PayPal Checkout by clicking "Enable PayPal Checkout" (see picture below):

[settings1]: _media/paypal/settings1.png "Enabling paypal checkout"
![alt text][settings1]

Because you will be creating your first PayPal Checkout form we advise you to enable the PayPal Sandbox mode.
 This is useful because now you can test as many times as you wish without making real transactions with real money.
 Instead you will be using your PayPal sandbox account. If you do not have one already please head over to [sandbox.paypal.com](http://sandbox.paypal.com) and create your first sandbox account.
 To enable the sandbox mode you can simply click on `Enable PayPal Sandbox mode (for testing purposes only)` as you can see in the below picture:

[enable-sandbox]: _media/paypal/enable-sandbox.png "Enabling PayPal Sandbox mode"
![alt text][enable-sandbox]

#### Step 3 – Adding merchant email to receive payments

Once you completed step 1 and 2 above, you can now go ahead and enter the PayPal merchant email.
 This will in most cases be your own paypal email address account where you wish to receive payments on.
 In some cases you might have a form that requires to dynamically retrieve the email address based on user selected information.
 In that case you can use {tags} to retrieve this data.
 For now just enter your sandbox email address (see picture below):

[merchant-email]: _media/paypal/merchant-email.png "Adding merchant email to receive payments"
![alt text][merchant-email]

#### Step 4 – Choosing your currency

The next important thing to change accordingly is the currency for the PayPal checkouts.
 Depending on your country you can change this to for instance `USD` ($), `EUR` (€) or any other currency supported by PayPal.

[currency]: _media/paypal/currency.png "Choosing your currency"
![alt text][currency]

#### Step 5 – Shipping address requirement setting

Normally when a user checkouts out via PayPal you would ask for an address. But because you might already have the address of the user (filled out in the form) you could let the user skip to enter their shipping address. There are 3 options you can choose from. The most common one tho choose would normally be the default value, but you can change it to any of the following if required:

[shipping-address]: _media/paypal/shipping-address.png "Shipping address requirement setting"
![alt text][shipping-address]

#### Step 6 – Choosing the payment method

In order to be able to checkout with PayPal, PayPal must know what type of payment it is that you are doing. It allows you to handle 4 different payment methods.
 Depending on your needs you can choose one of the following payment methods:

[payment-method]: _media/paypal/payment-method.png "Choosing the payment method"
![alt text][payment-method]

Each payment method is explained here:

* [Single product or service checkout](#single-product-or-service-checkout)
* [Donation checkout](#donation-checkout)
* [Subscription checkout](#subscription-checkout)
* [Cart checkout (for multiple product checkout)](#cart-checkout-for-multiple-product-checkout)

If you do not want information about each payment method you can click here to [Step 7 – Setting up return URL](#step-7-setting-up-return-url)

##### Single product or service checkout

This method is meant for only 1 product checkouts.
 When using this payment method you will only have to set the `Item description` (this will be your product name).
 The `Item description` option is compatible with {tags} so you can dynamically set this based on user selected options in your form.
 Of course your product has a price, which you can set under `Item price` (must be float number e.g: 12.59).
 The`Item price` option is also compatible with {tags} so you can also dynamically set the price based on user selected options in your form.
 The last requirement is the `Quantity` to be added to the PayPal checkout basket (must be a numeric value).

Below you can see all the settings with example values that you could enter:

[item-name-price-quantity]: _media/paypal/item-name-price-quantity.png "Single product or service checkout"
![alt text][item-name-price-quantity]

##### Donation checkout

The Donation checkout method is only used for... (you guessed it) donations.
 A donation checkout requires the same options as the `Single product or service checkout` method with the exception that there is no `Quantity` option available for this method.

##### Subscription checkout

The Subscription checkout can be used when you wish to create a new subscription for the user who filled out the form.
 When enabled you will be prompted to choose the `Item description` just like you would with the `Single product` and `Donation` methods.
 The Subscription checkout has an extra option to set `Subscription periods`. Here you will be able to adjust the time frame regarding the subscription.
 A subscription may also have a trial period and a second trial period. The Subscription periods option is compatible with [{tags}](tags-system.md) so you can dynamically create subscription time periods based on user selected options in your form. A good way to achieve this would be to use a [variable field](variable-fields.md).

Please refer to the below examples to fully understand how to set it up for your own use cases:

**Example without trial period:**

You want to create a subscription without a trial period that costs $20.50 p/m:

[subscription-periods-usecase1]: _media/paypal/subscription-periods-usecase1.png "Example without trial period"
![alt text][subscription-periods-usecase1]

**Example with 1 trial period:**

You want to create a subscription with 1 trial period for 3 days and after trial period is over $2 per week:

[subscription-periods-usecase2]: _media/paypal/subscription-periods-usecase2.png "Example with 1 trial period"
![alt text][subscription-periods-usecase2]

**Example with 2 trial periods:**

You want to create a subscription with 2 trial periods (1st 1 week trial), 2nd (2 weeks for $3 p/w), after that $18 p/m:

[subscription-periods-usecase3]: _media/paypal/subscription-periods-usecase3.png "Example with 2 trial periods"
![alt text][subscription-periods-usecase3]

##### Cart checkout (for multiple product checkout)

The Cart checkout will only be used and required whenever you want to send users to the PayPal checkout where they will checkout multiple products at once.
 In other words, it will function as a shopping bag/cart just like with a regular webshop.
 To add multiple items to the PayPal checkout you have to enter each item under the `Items to be added to cart` option.
 Each item can contain the following variables:

`{price}|{quantity}|{item_name}|{tax}|{shipping}|{shipping2}|{discount_amount}|{discount_rate}`

In most use cases you will only be using the first 3 options like so:

`{price}|{quantity}|{item_name}`

Where `price`, `quantity` and `item_name` should be replaced with your field names, please read the [Tags system](tags-system.md) for more information about tags.
 To fully understand how PayPal handles these variables please read the [PayPal's Variable Reference](https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/#individual-items-variables).
 Please refer to the below examples to fully understand how to set it up for your own use cases:

**Example with fixed values:**

You want to add 2 products to the paypal cart (5x Flowers $3.49) and (3x Towels $7.25):

[cart-checkout-usecase1]: _media/paypal/cart-checkout-usecase1.png "fixed items usecase"
![alt text][cart-checkout-usecase1]

**Example with dynamic price, retrieved from your form with tags:**

Based on user selected option you want to dynamically return the price for a fixed product and the user selected quantity:

[cart-checkout-usecase2]: _media/paypal/cart-checkout-usecase2.png "dynamically return the price usecase"
![alt text][cart-checkout-usecase2]

**Example with products inside a dynamic column:**

Let's say you have a [dynamic column](columns.md#dynamic-add-more) setup with super forms with inside quantity element, dropdown for product name, and a variable field that is updated based on the dropdown option.
 You will have multiple products depending on how many times the user would add a new set of fields by clicking the + button on the [dynamic column](columns.md#dynamic-add-more).
 You will be able to simply enter the following [{tags}](tags-system.md) and it will automatically add all the available options to the PayPal checkout.

[cart-checkout-usecase3]: _media/paypal/cart-checkout-usecase3.png "dynamic column usecase"
![alt text][cart-checkout-usecase3]

#### Step 7 – Setting up return URL

After you finished all the above steps and choosen your desired payment method for your checkout, you can now setup a proper return URL.
 This is the URL where a user will be redirect to after the user successfully returns from paypal. This can be any URL of your choosing.
 By default it will be `http://yourdomain.com/?page=super_paypal_response`
 PayPal will post information about the transaction in the form of Instant Payment Notification messages.
 This can optionally be used by the developer to display any data regarding the payment on the page.

?> **Notice:** make sure you properly change this to your own needs before going live.

#### Step 8 – Setting up cancel URL

This URL will be used to redirect the user back to your website after they canceled the checkout process on PayPal checkout page.
 User that cancels payment will be redirected to this URL. This can be any URL of your choosing, but by default it will be: `http://yourdomain.com/my-custom-canceled-page`

[cancel-url]: _media/paypal/cancel-url.png "Setting up cancel URL"
![alt text][cancel-url]

?> **Notice:** make sure you properly change this to your own needs before going live.

#### Step 9 – Sending E-mail after payment completed

Of course you'd like to notify your customer after the payment was completed.
 Perhaps you want to send them an attachment, or a signup URL, or just the overview of their order.
 You can do so by enabling the option `Send email after payment completed` and configuring the email settings.

#### Step 10 – Testing with sandbox account before going live

The last step is to test your form functioning before going live.
 Use your PayPal sandbox account to simulate payments and various form submissions.
 If you have created an advanced form with Super Forms, try to test as many of the possible variations your form offers before going live.
 If everything was setup correctly you should see transactions and/or subscriptions coming in under `Super Forms > PayPal transactions/subscriptions`.
 If you are not seeing any transactions coming in, you have to look in your sandbox account for response codes from the IPN.

[paypal-menu]: _media/paypal/paypal-menu.png "Testing with sandbox account before going live"
![alt text][paypal-menu]

**PayPal Transactions:**

[transactions]: _media/paypal/transactions.png "PayPal Transactions"
![alt text][transactions]

**PayPal Subscriptions:**

[subscriptions]: _media/paypal/subscriptions.png "PayPal Subscriptions"
![alt text][subscriptions]
