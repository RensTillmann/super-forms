---
description: >-
  Updating a comma separated string to Array for WordPress meta data via
  Front-end Posting when creating a new post after form submission.
---

# Toolset Plugin: Update comma separated string to Array for meta data saved via Front-end Posting

The below code is useful for `Checkbox` fields made within Toolset plugin. Since Super Forms Front-end Posting saves them as a comma separated string, we must convert it to an array so that Toolset can properly retrieve these values.

```php
function f4d_convert_metadata( $attr ) {
    // CHANGE THIS LIST TO ANY META DATA YOU NEED TO CONVERT
    $meta_keys = array( 'your_meta_key_here1', 'your_meta_key_here2', 'your_meta_key_here3' );
    // DO NOT CHANGE BELOW CODE
    $post_id = $attr['post_id'];
    foreach($meta_keys as $meta_key){
        // Grab meta value
        $meta_value = get_post_meta( $post_id, $meta_key, true );
        // Convert to Array
        $meta_value = explode(',', $meta_value);
        // Save it as array
        update_post_meta( $post_id, $meta_key, $meta_value );
    }
}
add_action('super_front_end_posting_after_insert_post_action', 'f4d_convert_metadata', 10, 1);
```
