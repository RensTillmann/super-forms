---
description: >-
  This code allows you to display the remaining amount of times the form is
  allowed to be submitted.
---

# Show remaining available form submission allowed

{% hint style="danger" %}
**Important:** In order for the below code to work properly you will require to enable the [Global form locker](../../features/advanced/lock-and-hide-form.md#lock-form-after-specific-amount-of-submissions) setting on your form.
{% endhint %}

{% hint style="info" %}
**Note:** The form locker/submission count is not connected to the Contact Entries created by the form. Deleting entries will not affect the counter. Instead it is based on the period chosen, Daily, Monthly, Yearly etc. as shown in the picture at the bottom of this page.
{% endhint %}

Copy and paste the below code at the bottom of your child theme **functions.php** file.

```php
function retrieve_submission_count_shortcode($atts) {
    extract(shortcode_atts(array('form_id' => 0), $atts));
    return absint(get_post_meta($form_id, '_super_submission_count', true));
}
add_shortcode('submission_count', 'retrieve_submission_count_shortcode');

function retrieve_remaining_submission_count_shortcode($atts){
    extract(shortcode_atts(array('form_id' => 0), $atts));
    $settings = SUPER_Common::get_form_settings($form_id);
    $limit = 0;
    if(!empty($settings['form_locker'])){
        if(!isset($settings['form_locker_limit'])) $settings['form_locker_limit'] = 0;
        $limit = $settings['form_locker_limit'];
    }
    $count = get_post_meta($form_id, '_super_submission_count', true);
    return $limit-absint($count);
}
add_shortcode('remaining_submission_count', 'retrieve_remaining_submission_count_shortcode');
```

You can now use the below shortcode to display the remaining amount of allowed submission for this form. Replace `123` with your form ID. You can use this shortcode anywhere on your page. But you can also use it inside your form [HTML Element](../../elements/html-elements/html-raw.md).

```
Total submissions (current period): [submission_count form_id="123"]
Remaining submissions (current period): [remaining_submission_count form_id="123"]
```

<div align="left"><figure><img src="../../.gitbook/assets/image (11).png" alt="WordPress form locker settings, reset submission counter every month."><figcaption><p>WordPress form locker settings, reset submission counter every month.</p></figcaption></figure></div>
