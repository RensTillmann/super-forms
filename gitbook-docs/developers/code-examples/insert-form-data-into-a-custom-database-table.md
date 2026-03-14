---
description: Insert form data into a custom WordPress database table upon form submission.
---

# Insert form data into a custom database table

In case you also want to clean up these rows when an entry is deleted you might want to read how to [delete a database row after an entry is deleted](delete-database-row-after-contact-entry-is-deleted-in-wordpress.md).

```php
add_action('super_before_email_success_msg_action', '_super_save_data_into_database', 10, 1);
function _super_save_data_into_database( $atts ) {

    // REPLACE 123 WITH YOUR FORM ID
    $id = 123;

    // REPLACE table_name WITH YOUR TABLE NAME
    $table = 'table_name';

    // CHANGE THE BELOW ARRAY AND ADD COLUMNS AND FIELDS ACCORDINGLY
    $fields = array(
        'column_name' => 'first_name', // replace column_name with correct column name for your table, and first_name with the appropriate field name from your form
        'column_name2' => 'last_name', // replace column_name with correct column name for your table, and first_name with the appropriate field name from your form
        // etc...
    );

    $form_id = absint($atts['post']['form_id']); // contains the form ID that was submitted
    if( $form_id == $id ) {
        global $wpdb;
        $data = $atts['data']; // contains the submitted form data
        $values = array();
        foreach( $fields as $k => $v ) {
            $values[$k] = $data[$v]['value'];
        }
        $values['entry_id'] = absint($atts['entry_id']);
        $wpdb->insert( $table, $values );
    }
}
```
