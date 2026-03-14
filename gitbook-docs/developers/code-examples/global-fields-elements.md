---
description: >-
  Need the same field or element on existing forms? The global field method
  might be helpful in these scenario's. To add a field to all your existing
  forms by altering the form via WordPress hooks.
---

# Global fields / elements

Let's assume you already created 50 forms, but you now require a hidden field that holds the current page URL.

At the time of writing Super Forms doesn't yet come with a feature that allows you to add a so called `Global field` .&#x20;

However you can easily achieve this with a small function like the one below.

The code will alter the current form elements array and add a new hidden field named `page_url` with the default value set to `{post_permalink}` which retrieves the current page URL.

Of course you can add multiple fields by altering the JSON code below. If you are not sure how to format them, you could create a temporary form in the back-end, add your fields and then view the `[CODE]` tab on the builder page to copy the JSON value.

If you only require this to be fired for specific forms, you can add a condition based on the `$form_id` parameter value.

{% hint style="warning" %}
Place the below code at the bottom of your child theme **functions.php**
{% endhint %}

```php
function f4d_add_hidden_field($value, $form_id, $meta_key, $single){
    $meta_needed = '_super_elements';
    if(isset($meta_key) && $meta_needed===$meta_key){
        // (optional condition: skip forms with the following ID) if($form_id===123 || $form_id===124) return;
        // (optional condition: only alter forms with the following ID) if($form_id!==123 && $form_id!==124) return;
        remove_filter('get_post_metadata', 'f4d_add_hidden_field', 10);
        $value = get_post_meta($form_id, $meta_needed, true);
        $json = '{"tag":"hidden","group":"form_elements","data":{"name":"page_url","email":"Hidden:","value":"{post_permalink}"}}';
        $value[] = json_decode($json, true);
        return array($value);
    }
    // Return original if the check does not pass
    return $value;
}
add_filter('get_post_metadata', 'f4d_add_hidden_field', 10, 4);
```
