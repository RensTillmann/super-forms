---
description: >-
  How to define custom POST request headers before making the request on your
  WordPress site based on the URL.
---

# Define custom headers when doing a POST request

```php
function f4d_modify_http_request_args($parsed_args, $url){
    // Replace XXX with whatever URL you put inside the "Enter a custom form post URL" setting
    // For example if you are doing a post request to https://api.domain.com/create/post
    // replace XXX with that exact URL
    if($url==='XXX'){
        // Update headers, of course you can also alter other arguments if needed
        // Default arguments provided by WordPress: https://github.com/WordPress/wordpress-develop/blob/97218bbfd336035edc9293274fea0f7bd3da85d7/src/wp-includes/class-wp-http.php#L150
        $parsed_args['headers'] = array(
            'Content-Type' => 'application/json; charset=utf-8',
            'ClientID' => 'xxx',
            'Authentication' => 'Basic & Base64 Encoded'
        );
    }
    return $parsed_args;
}
add_filter( 'http_request_args', 'f4d_modify_http_request_args', 10, 2 );
```
