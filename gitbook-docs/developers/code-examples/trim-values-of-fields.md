---
description: >-
  The below example codes will trim (remove any whitespace at the start and end
  of a value). This makes sure any spaces a user entered at the start or end are
  removed before processing.
---

# Trim values of fields

{% hint style="info" %}
_Insert the below PHP code in your child theme functions.php file, or create a custom plugin. You may also use a plugin that allows you to insert code snippets to your site._
{% endhint %}

### **Trim all fields:**

```php
add_filter('super_before_sending_email_data_filter', '_super_trim_all_values', 10, 2);
function _super_trim_all_values($data, $atts){
    foreach($data as $k => $v){
        if(isset($data[$k]['value'])) {
            $data[$k]['value'] = trim($data[$k]['value']);
        }
    }
    return $data;
}
```

### **Trims only specific fields:**

```php
add_filter('super_before_sending_email_data_filter', '_super_trim_values', 10, 2);
function _super_trim_values($data, $atts){
    // REPLACE 123 WITH YOUR FORM ID
    $id = 123;
    // DEFINE FIELD NAMES TO TRIM
    $fieldNames = array(
        'first_name',
        'last_name',
        'email'
    );

    $form_id = absint($atts['post']['form_id']); // contains the form ID that was submitted
    if($form_id==$id){
        foreach($fieldNames as $name){
            if(isset($data[$name]) && !empty($data[$name]['value'])){
                $data[$name]['value'] = trim($data[$name]['value']);
            }
        }
    }
    return $data;
}

add_filter('super_before_sending_email_data_filter', '_super_trim_all_values', 10, 2);
function _super_trim_all_values($data, $atts){
    foreach($data as $k => $v){
        if(isset($data[$k]['value'])) {
            $data[$k]['value'] = trim($data[$k]['value']);
        }
    }
    return $data;
}
```
