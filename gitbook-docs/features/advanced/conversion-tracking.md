---
description: How to track WordPress form submissions as conversions with Google Tag manager
---

# Conversion Tracking

Add the following code at the bottom of your child theme **functions.php** and set your CONVERSION\_LABEL accordingly. Make sure to also change the values that you wish to send for this conversion.&#x20;

These are values retrieved from the form submission itself. So any field that you are using on your form can be retrieved. In the above example we retrieve `product_name`, `quantity` and `total`.&#x20;

You will need to change them accordingly for your form. Optionally you can use the `args.form_id` to only execute the conversion tracking for specific forms only.

```php
add_action('wp_footer', function(){
    ?>
    <script type="text/javascript">
        // Execute after form submission
        if(typeof SUPER === 'undefined') window.SUPER = {}; // Custom JS script was loaded to early
        SUPER.custom_form_conversion_tracker = function(args){ // Grab form fields
            var product_name= (args.data.product_name? args.data.product_name.value : '');
            var quantity= (args.data.quantity? args.data.quantity.value : '');
            var total= (args.data.total? args.data.total.value : ''); 
            // Submit conversion event
            gtag('event', 'conversion', {
                'send_to': 'AW-CONVERSION_ID/CONVERSION_LABEL',
                'currency': 'USD',
                'formID': args.form_id,
                'product_name': product_name,
                'quantity' : quantity,
                'total' : total
            });
        }
    </script>
    <?php
}, 100);

// Add custom javascript function 
function f4d_custom_form_conversion_tracker( $functions ) {
    $functions['after_email_send_hook'][] = array(
        'name' => 'custom_form_conversion_tracker'
    );
    return $functions;
}
add_filter( 'super_common_js_dynamic_functions_filter', 'f4d_custom_form_conversion_tracker', 100, 2 );
```
