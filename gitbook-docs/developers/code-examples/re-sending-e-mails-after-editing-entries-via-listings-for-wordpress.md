---
description: >-
  The code example will make it possible to re-send the Admin and or
  Confirmation E-mails after an entry has been edited or updated via the table
  view (Listings Add-on) on your WordPress site.
---

# Re-sending E-mails after editing entries via Listings for WordPress

Send Admin email or Confirmation Email again after editing an entry via the Listings Add-on. In the below code example we changed the `send` and `confirm` to **yes**. That way when you edit an entry in the table view on your WordPress site it will trigger the emails again.

You can add the below code into your child theme **functions.php** file.

```php
add_filter('super_before_submit_form_settings_filter', 'f4d_alter_form_settings_before_submit', 50, 2); 
function f4d_alter_form_settings_before_submit($settings, $args){ 
    extract($args); 
    if($list_id!==''){ 
        // In order to edit entries we need to make sure some settings are not enabled  
        $overrideSettings = array( 
            'send'=>'yes', // changed to 'yes' so that the form will re-send the email
            'confirm'=>'yes' // changed to 'yes' so that the form will re-send the email
        ); 
        $global_settings = SUPER_Common::get_global_settings(); 
        $i = 1; 
        while($i <= absint($global_settings['email_reminder_amount'])){ 
            $overrideSettings['email_reminder_'.$i] = ''; 
            $i++; 
        } 
        foreach($overrideSettings as $k => $v){ 
            $settings[$k] = $v; 
        } 
    } 
    return $settings; 
}
```
