---
description: >-
  Prevent form from being submitted by comparing entered field values. Useful
  when you want to prevent a specific user or bot from submitting the form.
  Returns a fake success message to the user.
---

# Prevent form submission based on entered field values

{% hint style="info" %}
The below action hook only works with Super Forms **v6.3.708+**
{% endhint %}

```php
add_action('super_before_processing_data', function($atts){
  $data = $atts['atts']['data'];
  // Return fake message when one of the following fields matches the value
  $checks = array(
    'email' => array(
      'bot@bot.com',
      'bot2@bot.com',
      'bot3@bot.com',
    ),
    'phonenumber' => array(
      '1231231231',
      '1231231232',
      '1231231233',
    ),
    'my_field_name' => array(
      'value_to_compare_with',
      'value_to_compare_with2',
      'value_to_compare_with3',
    )
  );
  foreach($checks as $fieldName => $compare){
    // Skip if field with this name does not exists in the form
    if(!isset($data[$fieldName])) continue; 
    // Compare the entered value in the form with the one we defined
    if(in_array(trim($data[$fieldName]['value']), $compare)) {
      SUPER_Common::output_message(array(
        'error'=>false,
        'msg' => '<h1>Thank you!</h1>We will reply within 24 hours (fake message to pretent succesful submission, but nothing really happened)'
      ));
    }
  }
});
```
