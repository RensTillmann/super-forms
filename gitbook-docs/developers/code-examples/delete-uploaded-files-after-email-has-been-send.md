---
description: >-
  When your WordPress form is submitted, delete any uploaded files directly
  after the email has been send.
---

# Delete uploaded files after email has been send

{% hint style="warning" %}
**Note:** This code should no longer be used, you can now configure this via **Super Forms > Settings > File Upload Settings**
{% endhint %}

```php
add_action('super_before_email_success_msg_action', '_super_delete_uploaded_files', 30, 1);
function _super_delete_uploaded_files( $atts ) {

    // REPLACE 123 WITH YOUR FORM ID
    $id = 123;

    // CHANGE AND ADD THE NAMES OF FILE UPLOAD FIELDS
    $fields = array(
        'file1',
        'file2',
        'file3',
    );

    $form_id = absint($atts['post']['form_id']); // contains the form ID that was submitted
    if( $form_id == $id ) {
        $data = $atts['data']; // contains the submitted form data
        foreach( $fields as $field_name ) {
            if( isset( $data[$field_name]['files'] ) ) {
                $files = $data[$field_name]['files'];
                if( is_array( $files ) ) {
                    foreach( $files as $file ) {
                        wp_delete_attachment(absint($file['attachment']), true);
                    }
                }
            }
        }
    }
}
```
