---
description: How to exclude empty fields from your WordPress email after form submission
---

# Exclude empty fields from emails

{% hint style="info" %}
Super Forms also has an option that does this for you on the form builder page under **Form Settings > Confirmation E-mail > Exclude empty values from email loop**
{% endhint %}

```php
add_filter( 'super_before_sending_email_data_filter', '_super_exclude_empty_field_from_email', 10, 2 );
function _super_exclude_empty_field_from_email( $data, $atts ) {

    // REPLACE 123 WITH YOUR FORM ID
    $id = 123;

    $form_id = absint($atts['post']['form_id']); // contains the form ID that was submitted

    if( $form_id == $id ) {
        foreach( $data as $k => $v ) {
            if( $v['type']=='files' ) {
                if( !isset($v['files']) ) {
                    // 1 = Exclude from confirmation email only
                    // 2 = Exclude from all emails
                    $data[$k]['exclude'] = 2;
                }
                continue;
            }

            // We exclude whenever the field value equals 0 or when the value was empty
            if( ($v['value']=='0') || ($v['value']=='') ) {
                // 1 = Exclude from confirmation email only
                // 2 = Exclude from all emails
                $data[$k]['exclude'] = 2;
            }
        }
    }
    return $data;
}
```
