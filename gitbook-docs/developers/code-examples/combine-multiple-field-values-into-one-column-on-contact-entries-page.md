---
description: >-
  This code allows you to combine multiple field values into one single column.
  For instance if you have multiple event dates you could combine them into a
  single column named "Event Dates"
---

# Combine multiple field values into one column on Contact Entries page

{% hint style="info" %}
Instead of using the custom PHP code below, you could also use a [Variable field](../../features/advanced/variable-fields.md), setting it to hold {tag1}, {tag2}, {tag3} and using this as your final value to create a single column that displays multiple field values.
{% endhint %}

Place the below code into your child theme **functions.php** file.&#x20;

Edit the `Event Date` column name and define your field names e.g. `date_1` `date_2` `date_3` etc. This code will then comma seperate the fields into a single value placing it in a single column on the Contact Entires page.

```php
add_filter( 'manage_super_contact_entry_posts_columns', 'f4d_super_contact_entry_date_column', 9999999 );
function f4d_super_contact_entry_date_column($columns){
    $name = 'Event Date'; // Change this to your column name
    $columns = array_merge( $columns, array('f4d_custom_date_column' => $name));
    return $columns;
}
add_action('manage_super_contact_entry_posts_custom_column', 'f4d_super_custom_date_column', 10, 2);
function f4d_super_custom_date_column($column, $post_id){
    if($column=='f4d_custom_date_column'){
        // Define your field names
        $fields = array('date_1','date_2','date_3');
        $contact_entry_data = get_post_meta($post_id, '_super_contact_entry_data');
        $final_value = '';
        foreach($fields as $field_name){
            if(isset($contact_entry_data[0][$field_name]) && isset($contact_entry_data[0][$field_name]['value'])){
                $value = $contact_entry_data[0][$field_name]['value'];
                if($final_value===''){
                    $final_value .= $value;
                    continue;
                }
                $final_value .= ', '.$value;
            }
        }
        echo $final_value;
    }
}
```
