---
description: >-
  Increasing WordPress cookie lifetime for client data such as form progression
  and other client storage required by some of Super Forms features.
---

# Increase Cookie lifetime for client data such as \[Form Progression]

{% hint style="info" %}
**Note:** These filter hooks are available since **Super Forms v6.3+**. Always set the cookie lifetime to a higher value than the client data lifetime, for instance if you set the cookie expiry to 10 hours, your client data can't exceed 10 hours simply because the session would already be deleted along with any data. This allows you to define specific client data with a shorter lifetime than others, while having a single session ID for the current client.
{% endhint %}

```php
function f4d_super_cookie_expires_filter($expires) {
    // Please note: this lifetime must be higher than what you set for `super_client_data_expires_filter`
    return 60*60; // Increase or decrease the session lifetime, by default the expiry is set to 1 hour (3600) or (60*60)
}
add_filter( 'super_cookie_expires_filter', 'f4d_super_cookie_expires_filter' );

function f4d_super_cookie_exp_var_filter($exp_var) {
    return 20*60; // Increase or decrease the session update/extend lifetime, by default the expiry is set to 20 min. (1200) or (20*60)
}
add_filter( 'super_cookie_exp_var_filter', 'f4d_super_cookie_exp_var_filter' );

function f4d_super_client_data_expires_filter($expires) {
    // Please note: this lifetime must be lower than what you set for `super_cookie_expires_filter`
    return 30*60; // Increase or decrease the client data lifetime, by default the expiry is set to 30 min. (1800) or (30*60)
}
add_filter( 'super_client_data_expires_filter', 'f4d_super_client_data_expires_filter' );

function f4d_super_client_data_exp_var_filter($exp_var) {
    return 10*60; // Increase or decrease the client data update/extend lifetime, by default the expiry is set to 10 min. (600) or (10*60)
}
add_filter( 'super_client_data_exp_var_filter', 'f4d_super_client_data_exp_var_filter' );

// Filtering lifetime for specific client data is also possible, simply replace $name with the data that is being saved, for instance `progress_1234` where 1234 would be your form ID
add_filter( 'super_client_data_$name_expires_filter', 'f4d_super_client_data_expires_filter' );
add_filter( 'super_client_data_$name_exp_var_filter', 'f4d_super_client_data_exp_var_filter' );
```
