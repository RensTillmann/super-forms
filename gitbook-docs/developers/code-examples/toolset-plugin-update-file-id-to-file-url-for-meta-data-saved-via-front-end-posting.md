---
description: >-
  Updating the file ID to point to the file URL for WordPress meta data when
  saving a post via Front-end Posting.
---

# Toolset Plugin: Update file ID to file URL for meta data saved via Front-end Posting

The below code is useful for `File` fields made within Toolset plugin. Since Super Forms Front-end Posting stores only the file ID, and Toolset requires the file URL, we must retrieve the file URL and override the meta value in order for Toolset to display the file.

```php
function f4d_convert_file_id_to_url( $attr ) {
    // CHANGE THIS LIST TO ANY META DATA YOU NEED TO CONVERT
    $meta_keys = array( 'your_file_meta_key_here1', 'your_file_meta_key_here2' );
    // DO NOT CHANGE BELOW CODE
    $post_id = $attr['post_id'];
    foreach($meta_keys as $meta_key){
        // Grab meta value
        $file_id = get_post_meta( $post_id, $meta_key, true );
        // Grab file URL based on file ID
        $file_url = wp_get_attachment_url( $file_id );
        // Save it as array
        update_post_meta( $post_id, $meta_key, $file_url );
    }
}
add_action('super_front_end_posting_after_insert_post_action', 'f4d_convert_file_id_to_url', 10, 1);
```
