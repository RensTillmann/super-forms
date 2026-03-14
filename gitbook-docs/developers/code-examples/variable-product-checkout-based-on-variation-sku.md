---
description: >-
  This example code allows you to use variable fields inside your form to
  generate the SKU dynamically based on user selected options. Allowing you to
  add the product variation to the cart base on SKU.
---

# Variable product checkout based on variation SKU

Below PHP code is an example where we would ask a user to select their base product, a color, and the size of the product. We then use a variable field to create the desired SKU e.g. `{product}_{color}_{size}` which the script then translates to the actual variation ID.

Simply define the `Enter the product(s) ID that needs to be added to the car` h in your WooCommerce Checkout settings, to `{sku}|{quantity}`  where your form would contain a field named `sku` which will hold the generated SKU.&#x20;

Of course an actual SKU should exists in order for the product to be added to the cart.

You can add below to your child theme `functions.php`

```php
// Add WooCommerce variable product to cart based on variation SKU, e.g. if you have SKU: `product1_red_xxl` 
// the below script will lookup the variation ID based on this SKU, and update the passed variation_id to the WC add_to_cart() function
// In the below code we added a check to based on variable field value 
// Make sure to set the WooCommerce setting `Enter the product(s) ID that needs to be added to the cart` to something like:
// {sku}|{quantity}
// where `sku` would be your variable field that would set the correct SKU based on user selected options in your form e.g: `{product}_{color}_{size}`
// below filter is only available from the latest github commit (16 May, 2025)
add_filter('super_before_adding_wc_products_to_cart_filter', 'superforms_resolve_variation_from_id_sku', 10, 2);
function superforms_resolve_variation_from_id_sku($products, $context){
    foreach($products as &$product){
        if(isset($product['id']) && is_string($product['id'])){
            $sku = trim($product['id']);
            // (optional) Check if SKU has exactly 3 parts (e.g., product1_red_xxl)
            // $parts = explode('_', $sku);
            // if(count($parts)!==3) continue; // Not a custom SKU
            // Try to resolve variation by SKU
            $variation_id = wc_get_product_id_by_sku($sku);
            if(!$variation_id){
                wc_add_notice("Invalid SKU: $sku", 'error');
                continue;
            }
            $variation = wc_get_product($variation_id);
            if(!$variation || !$variation->is_type('variation')){
                wc_add_notice("SKU does not match a product variation: $sku", 'error');
                continue;
            }
            $parent_id   = $variation->get_parent_id();
            $attributes  = $variation->get_attributes();
            // Override fields
            $product['id'] = $parent_id;
            $product['variation_id'] = $variation_id;
            $product['variation_attributes'] = $attributes;
        }
    }
    return $products;
}
```

Example form code:

```json
[
    {
        "tag": "dropdown",
        "group": "form_elements",
        "data": {
            "name": "product",
            "email": "Option:",
            "dropdown_items": [
                {
                    "checked": false,
                    "label": "A",
                    "value": "a"
                },
                {
                    "checked": false,
                    "label": "B",
                    "value": "b"
                },
                {
                    "checked": false,
                    "label": "C",
                    "value": "c"
                }
            ],
            "placeholder": "- select a option -",
            "icon": "caret-square-down;far"
        }
    },
    {
        "tag": "quantity",
        "group": "form_elements",
        "data": {
            "name": "quantity",
            "email": "Quantity:",
            "minnumber": "1"
        }
    },
    {
        "tag": "dropdown",
        "group": "form_elements",
        "data": {
            "name": "frequency",
            "email": "Option:",
            "dropdown_items": [
                {
                    "checked": false,
                    "label": "Monthly",
                    "value": "monthly"
                },
                {
                    "checked": false,
                    "label": "Yearly",
                    "value": "yearly"
                }
            ],
            "placeholder": "- select a option -",
            "icon": "caret-square-down;far"
        }
    },
    {
        "tag": "dropdown",
        "group": "form_elements",
        "data": {
            "name": "payment",
            "email": "Option:",
            "dropdown_items": [
                {
                    "checked": false,
                    "label": "Monthly",
                    "value": "monthly"
                },
                {
                    "checked": false,
                    "label": "Yearly",
                    "value": "yearly"
                }
            ],
            "placeholder": "- select a option -",
            "icon": "caret-square-down;far"
        }
    },
    {
        "tag": "hidden",
        "group": "form_elements",
        "data": {
            "name": "sku",
            "email": "Variable:",
            "conditional_variable_action": "enabled",
            "conditional_variable_items": [
                {
                    "field": "{product}",
                    "logic": "not_equal",
                    "value": "",
                    "and_method": "",
                    "field_and": "",
                    "logic_and": "",
                    "value_and": "",
                    "new_value": "{product}_{frequency}_{payment}"
                }
            ]
        }
    },
    {
        "tag": "html",
        "group": "html_elements",
        "data": {
            "name": "html",
            "email": "HTML:",
            "html": "product: {product}\nfrequency: {frequency}\npayment: {payment}\n\n\nSKU value: {sku}",
            "exclude": "2",
            "exclude_entry": "true"
        }
    }
]
```
