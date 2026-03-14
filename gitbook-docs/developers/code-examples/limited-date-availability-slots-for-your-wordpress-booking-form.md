---
description: >-
  Allow only a maximum amount of bookings (slots) for a specific date for your
  WordPress booking form. And display an error message when the date is fully
  booked and no longer available for selection.
---

# Limited date availability (slots) for your WordPress booking form

In the example below when the form is being submitted, the [Datepicker field](../../elements/form-elements/datepicker.md) named `date` is compared to any previously submitted form with the same date. In this example, when the amount of bookings for this date exceeds 5, the user will receive a message that this date is fully booked and that they require to choose a different date.&#x20;

The below code can be placed inside your Child theme `functions.php` file.

{% hint style="info" %}
In the above URL's the `secret` should be equal to the secret defined in your function(s). The`fieldName` parameter should be equal to the [Datepicker field](../../elements/form-elements/datepicker.md) name in your form. The `date` parameter should equal the date value itself in the same format that the Datepicker field is using e.g. `28-07-2023` when your date format is set to `dd-mm-yyyy`. The `booked` parameter should be set to the slots currently being taken/used. When setting `booked` to `-1` it will decrease the slots by one, allowing one extra booking for this date to become available.
{% endhint %}

The last function will automatically increase it's availability (in other words, one extra booking slot will become available) when an Contact Entry is being deleted (or trashed if you prefer).

### Limit the amount of bookings for a specific date

The below code will check if a specific date has been booked previously and if it doesn't exceed `5` bookings already. If it does, it will display a message to the user that the selected date is already fully booked, and that they require to choose a different date in order to continue.

{% hint style="warning" %}
Update the `$fieldName` and `$slots` to the desired values in the below code
{% endhint %}

```php
// Return message if availability exceeded (date is fully booked)
add_action( 'super_before_sending_email_hook', 'f4d_check_availability', 1 );
function f4d_check_availability($x){
    // Datepicker field name:
    $fieldName = 'date'; // change this to your datepicker field name
    $slots = 5; // allow 5 bookings for the same date in total
    // Extract form data
    extract(shortcode_atts(array('data'=>array()), $x));
    $data = wp_unslash($data);
    // Skip if field name does not exists
    if(!isset($data[$fieldName])) return;
    $date = trim(sanitize_text_field($data[$fieldName]['value']));
    $msg = 'The selected date `'.$date.'` is fully booked! Please select a different date';
    // Get the date slots, returns `0` if it doesn't exist yet
    $slots = get_option('_sf_'.$fieldName.'_slots_' . $date, 0);
    // Allow a maximum of 5 bookings for this date
    if(absint($slots)>=10){
        // Maximum is already reach return error message
        echo json_encode(
            array(
                'error' => true,
                'fields' => array($fieldName), // field that contains errors
                'msg' => $msg
            )
        );
        die();
    }
    // Continue as per usual with the form submission
    $slots++;
    update_option('_sf_'.$fieldName.'_slots_' . $date, $slots);
}

```

### Increase availability after Contact Entry is deleted

The below code will automatically increase the availability for the given date when an Contact Entry is being deleted (this does not include trashed entries).

```php
// When an entry is deleted increase the availability
add_action( 'before_delete_post', 'f4d_increase_availability_after_deleting_entry' );
function f4d_increase_availability_after_deleting_entry($entry_id){
    if(get_post_type($entry_id)=='super_contact_entry'){
        $fieldName = 'date';
        $data = get_post_meta( $entry_id, '_super_contact_entry_data', true );
        if(!isset($data[$fieldName])){
            return;
        }
        $date = $data[$fieldName]['value'];
        // First grab the current availability 
        $slots = get_option('_sf_'.$fieldName.'_slots_' . $date, 0);
        if(absint($slots)>0){
            $slots--; // decrease by 1
        }
        // Set the new availability
        update_option('_sf_'.$fieldName.'_slots_' . $date, $slots);
    }
}
```

### Manually reset/set the availability for a specific date

This function allow you to manually update/reset the availability for a specific date by calling the following URL's.

**Update to specific amount of bookings placed:**

This will update the slots to `X` indicating that a total of `X` bookings have been placed for this specific date. If your maximum slots is set to `20` it means an additional 13 bookings in total can be placed for this same date.

```
https://domain.com/?secret=Xj93Zy&fieldName=date&date=08-07-2023&slots=7
```

**Increase availability by one:**

This will remove one slot, allowing one additional booking to become available for the given date.

```
https://domain.com/?secret=Xj93Zy&fieldName=date&date=08-07-2023&availability=+1
```

**Decrease availability by one:**

This will add one slot, allowing one less booking to be available for the given date.

```
https://domain.com/?secret=Xj93Zy&fieldName=date&date=08-07-2023&availability=-1
```

<pre class="language-php"><code class="lang-php"><strong>// Manually set/reset the availability for a specific date
</strong>add_action( 'parse_request', 'f4d_set_availability_manually' );
function f4d_set_availability_manually(){
    // URL's:
    // Update to specific value: https://domain.com/?fieldName=date&#x26;date=08-07-2023&#x26;slots=7
    // Increase by one: https://domain.com/?fieldName=date&#x26;date=08-07-2023&#x26;availability=1
    // Decrease by one: https://domain.com/?fieldName=date&#x26;date=08-07-2023&#x26;availability=-1
    $secret = 'Xj93Zy';
    if(isset($_GET['secret']) &#x26;&#x26; $_GET['secret']==$secret &#x26;&#x26; isset($_GET['fieldName']) &#x26;&#x26; isset($_GET['date']) &#x26;&#x26; (isset($_GET['slots']) || isset($_GET['availability']))){
        $fieldName = sanitize_text_field($_GET['fieldName']);
        $date = sanitize_text_field($_GET['date']);
        $slots = absint(get_option('_sf_'.$fieldName.'_slots_' . $date, 0));
        if(isset($_GET['slots'])){
            $slots = absint($_GET['slots']);
        }
        if(isset($_GET['availability'])){
            $availability = $_GET['availability'];
            if($availability=='1'){
                // increase
                if($slots>0){
                    $slots--;
                }
            }
            if($availability=='-1'){
                // decrease
                $slots++;
            }            
        }
        // Set the new availability
        update_option('_sf_'.$fieldName.'_slots_' . $date, $slots);
        echo 'Total booking slots updated to `'.$slots.'` for date `'.$date.'` with field name `'.$fieldName.'`.';
        die();
    }
}
</code></pre>
