---
description: >-
  If you have a form with a dropdown and you wish to attach a specific file to
  the E-mails based on the selected dropdown item you can use the below code to
  achieve this.
---

# Altering the attachments for E-mails via PHP code for WordPress

{% hint style="info" %}
Place below code in your child theme **functions.php** file
{% endhint %}

You can use the `super_before_sending_email_attachments_filter` for admin E-mails and the `super_before_sending_email_confirm_attachments_filter` for Confirmation E-mails.

In the below code make sure to replace the `your_dropdown_name` with the field name of your dropdown element. Also make sure to correctly map the dropdown values with the corresponding file ID's that are stored on your WordPress site. In the below example they are mapped to the dropdown item value `option1`, `option2` and `option3`.

```php
add_filter('super_before_sending_email_attachments_filter', 'f4d_custom_attach_file_based_on_dropdown', 10, 2);
function f4d_custom_attach_file_based_on_dropdown($attachments, $atts) {
    // Retrieve the form data
    $form_data = $atts['data'];
    
    // Replace 'your_dropdown_name' with the actual name or ID of your dropdown field
    $dropdown_name = 'your_dropdown_name';
    
    // Ensure the dropdown field exists
    if (isset($form_data[$dropdown_name])) {
        $dropdown_value = $form_data[$dropdown_name]['value'];
        
        // Define your attachments based on dropdown value
        $attachments_map = array(
            'option1' => 1234, // ID of the attachment in WP
            'option2' => 1235, // ID of the attachment in WP
            'option3' => 1236  // ID of the attachment in WP
        );

        // Check if the selected value has an attachment
        if (isset($attachments_map[$dropdown_value])) {
            // Add the attachment to the attachments array
            $attachments[$dropdown_value.'.pdf'] = wp_get_attachment_url($attachments_map[$dropdown_value]);
        }
    }
    return $attachments;
}
```
