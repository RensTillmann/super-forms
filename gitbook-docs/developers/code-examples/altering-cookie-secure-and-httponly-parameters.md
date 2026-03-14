---
description: >-
  Change how your the cookies are stored by altering the `secure` and `httponly`
  parameters.
---

# Altering cookie secure and httponly parameters

{% hint style="info" %}
**Note:** If needed you can change how the cookie is being stored, by default when is\_ssl() returns true, a secure cookie will be stored. The cookie HttpOnly parameter is always set to true unless you define otherwise
{% endhint %}

```php
function f4d_super_cookie_secure_filter($secure) {
    return true; // set to false to disable
}
add_filter( 'super_cookie_secure_filter', 'f4d_super_cookie_secure_filter' );

function f4d_super_cookie_httponly_filter($httponly) {
    return false; // set HttpOnly parameter for the cookie to false, by default this is set to true
}
add_filter( 'super_cookie_httponly_filter', 'f4d_super_cookie_httponly_filter' );

function f4d_super_client_data_delete_limit_filter($limit) {
    return 10; // Maximum items to delete per query when cleaning up old client data (by default this value is set to 10)
}
add_filter( 'super_client_data_delete_limit_filter', 'f4d_super_client_data_delete_limit_filter' );
```
