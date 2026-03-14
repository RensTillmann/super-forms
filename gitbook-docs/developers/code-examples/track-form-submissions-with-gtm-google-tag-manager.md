---
description: Tracking WordPress form submissions with GTM (google tag manager)
---

# Track form submissions with GTM (Google Tag Manager)

{% hint style="info" %}
_Insert the below PHP code in your child theme functions.php file, or create a custom plugin. You may also use a plugin that allows you to insert code snippets to your site._
{% endhint %}

```php
add_action('wp_footer', function(){
    ?>
    <script type="text/javascript">
        // Execute after form submission
        if(typeof SUPER === 'undefined') {
            // Custom JS script was loaded to early
            window.SUPER = {};
        }
        SUPER.custom_form_tracker = function(args){
            // Grab form fields
            var product_name= (args.data.product_name? args.data.product_name.value : '');
            var quantity= (args.data.quantity? args.data.quantity.value : '');
            var total= (args.data.total? args.data.total.value : '');
            var utm_source= (args.data.utm_source? args.data.utm_source.value : ''); 
            // Your third party code here
            window.dataLayer = window.dataLayer || []
            dataLayer.push({
                'event': 'superFormsSubmission',
                'formID': args.form_id,
                'product_name': product_name,
                'quantity' : quantity,
                'total' : total,
                'utm_source': utm_source
            });
        }
    </script>
    <?php
}, 100);

// Add custom javascript function 
function f4d_add_dynamic_function( $functions ) {
    $functions['after_email_send_hook'][] = array(
        'name' => 'custom_form_tracker'
    );
    return $functions;
}
add_filter( 'super_common_js_dynamic_functions_filter', 'f4d_add_dynamic_function', 100, 2 );
```
