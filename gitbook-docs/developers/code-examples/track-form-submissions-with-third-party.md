---
description: Tracking form submission with Third Party software
---

# Track form submissions with third party

{% hint style="info" %}
_Insert the below PHP code in your child theme functions.php file, or create a custom plugin. You may also use a plugin that allows you to insert code snippets to your site_
{% endhint %}

```php
// Load f4d-custom.js
function f4d_enqueue_script() {
    wp_enqueue_script( 'f4d-custom', plugin_dir_url( __FILE__ ) . 'f4d-custom.js', array( 'super-common' ) );
}
add_action( 'wp_enqueue_scripts', 'f4d_enqueue_script' );
add_action( 'admin_enqueue_scripts', 'f4d_enqueue_script' );

// Add custom javascript function
function f4d_add_dynamic_function( $functions ) {
    $functions['after_email_send_hook'][] = array(
        'name' => 'after_form_submission'
    );
    return $functions;
}
add_filter( 'super_common_js_dynamic_functions_filter', 'f4d_add_dynamic_function', 100, 2 );
```

JS script (f4d-custom.js)

```javascript
(function($) {
    // Execute after form submission
    SUPER.after_form_submission = function($form){
        // Your third party code here
        alert('Your third party code here');
    }
})(jQuery);
```
