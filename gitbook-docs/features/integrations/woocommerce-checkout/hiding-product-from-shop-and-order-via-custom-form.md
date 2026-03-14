---
description: >-
  How to hide a product from the WooCommerce shop and only allowing users to
  order the product via a custom form.
---

# Hiding product from shop and order via custom form

In order to only allow users to order the product via the form you build, you will have to follow the below steps:

1. Make sure you are running Super Forms **v4.9.800** or higher
2.  Go to **Super Forms > Settings > WooCommerce Checkout**:<br>

    <figure><img src="../../../.gitbook/assets/super-forms-global-woocommerce-checkout-settings.png" alt="WooCommerce Checkout settings"><figcaption><p>WooCommerce Checkout settings</p></figcaption></figure>
3.  Map the form with the product ID's and or Product category slugs under the **Hide products from the shop** setting. In the below example we will hide all products that belong to the category "Computers" which slug is "computers".<br>

    <figure><img src="../../../.gitbook/assets/hide-products-from-the-shop-by-category-or-product-id-woocommerce.png" alt="Hiding WooCommerce products from the shop based on Category slug or product ID."><figcaption><p>Hiding WooCommerce products from the shop based on Category slug or product ID.</p></figcaption></figure>
4.  Click **Save Settings**<br>

    <figure><img src="../../../.gitbook/assets/super-forms-save-global-settings.png" alt="Save your WooCommerce settings"><figcaption><p>Save your WooCommerce settings</p></figcaption></figure>
5. Visit your shop Front-end and confirm the products are no longer visible. Also confirm you can still order them via your forms.
