---
description: >-
  If you require to update some user meta data after the user logged in to your
  WordPress site you can use one of the below codes.
---

# Updating WordPress user meta data after login

### Method 1 (recommended)

{% hint style="warning" %}
This method requires Super Forms **v6.3.709** or higher to work
{% endhint %}

The preferred method would be to use the build in Super Forms hook, using the code below. Simply replace `push_id` with your form field name and `custom_user_meta_data_key` with the user meta data key that you would like to update.

```php
add_action('super_before_login_user_action', '_super_update_user_meta_data_after_login', 1);
function _super_update_user_meta_data_after_login($x){
    extract(shortcode_atts(array('data'=>array(), 'user_id'=>0), $x));
    // We want to update some user meta data from a hidden field named `push_id`
    $fieldName = 'push_id';
    $metaKey = 'custom_user_meta_data_key';
    // In case the field is empty, we don't do anything since the user might have logged in via other means
    if(empty($data[$fieldName])) return; 
    $data = wp_unslash($data);
    $metaValue = trim(sanitize_text_field($data[$fieldName]['value']));
    // Now update user meta 
    update_user_meta($user_id, $metaKey, $metaValue); 
}
```

### Method 2

{% hint style="warning" %}
This method also works for any Super Forms version
{% endhint %}

The second option is to use a WordPress core hook. In this example you will have to do a couple of more steps in order to get the desired result. And you have to take not that this will not be executed if the user tries to login while they where already logged in. That's why it's recommended to use the build in Super Forms hook instead. When using the below code, make sure to replace both the `my_form_field_name` and `my_custom_user_meta_key` in the code below accordingly so that they match your form field name and the meta key that requires updating.

```php
add_action('set_current_user', '_super_set_current_user', 10); 
function _super_set_current_user(){ 
    $user_id = get_current_user_id(); 
    if($user_id==0) return; 
    if(empty($_POST['data'])) return; 
    $data = array(); 
    $data = wp_unslash($_POST['data']); 
    $data = json_decode($data, true); 
    $data = wp_slash($data); 
    if(empty($data['my_form_field_name'])) return; 
    $onesignal_push_id = $data['my_form_field_name']['value']; 
    // Update user meta 
    update_user_meta($user_id, 'my_custom_user_meta_key', $onesignal_push_id); 
}
```
