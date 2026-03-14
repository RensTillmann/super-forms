---
description: >-
  The below code will delete a row from a custom WordPress database table when a
  contact entry is deleted. The below example will try to match the row based on
  the email address of the user.
---

# Delete database row after contact entry is deleted in WordPress

The below code matches based on the `email` column, but you can change this to whatever your usecase might be. For instance if you use unique codes to track entries you can do that as well.

You can also match it based on the `$entry_id` as long as you made sure you stored this when [inserting the row into your custom database](insert-form-data-into-a-custom-database-table.md) which is enabled by default under the column `entry_id`.

```php
add_action('before_delete_post', 'f4d_delete_entry_attachments'); 
function f4d_delete_entry_attachments($entry_id){ 
    if(get_post_type($entry_id)=='super_contact_entry'){ 
        $data = get_post_meta($entry_id, '_super_contact_entry_data', true); 
        // Delete row from database based on application code 
        global $wpdb; 
        $table = 'wp_custom_table'; 
        $where_condition = array('email' => $data['email']['value']); 
        $wpdb->delete($table , $where_condition); 
    } 
}
```
