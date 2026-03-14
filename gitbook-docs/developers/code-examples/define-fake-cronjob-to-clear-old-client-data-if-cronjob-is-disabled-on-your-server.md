---
description: >-
  How to define a fake cronjob for your WordPress site when cronjob is disabled
  on your server.
---

# Define fake cronjob to clear old client data if cronjob is disabled on your server

{% hint style="info" %}
These hooks don't need to be changed in normal circumstances, only use these if you know what you are doing. By default Super Forms will clean up expired client data each 1 out of 50 requests, if you wish to increase this you can increase it to 500 or 9999 as an example.
{% endhint %}

```php
function f4d_super_delete_old_client_data_manually_interval_filter($limit) {
    return 50; // Trigger the function to delete old client data roughly 1 out of 50 requests
}
add_filter( 'super_delete_old_client_data_manually_interval_filter', 'f4d_super_delete_old_client_data_manually_interval_filter' );

function f4d_super_delete_client_data_manually_limit_filter($limit) {
    return 10; // When deleting client data via manual request, we only want to delete 10 sessions at a time
}
add_filter( 'super_delete_client_data_manually_limit_filter', 'f4d_super_delete_client_data_manually_limit_filter' );
```
