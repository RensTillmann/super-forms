---
description: Comparing WordPress database value with form input value
---

# Compare input field value with database value

{% hint style="info" %}
_Insert the below PHP code in your child theme functions.php file, or create a custom plugin. You may also use a plugin that allows you to insert code sinppets to your site._
{% endhint %}

In this example we validate our form field named `code` with our database codes list. The database name is `db_name` and table name is `wp_codes`. The column is named `code` which we use for our comparison.

When the code entered by the user does not exists, we will display an error message to the user, and the form will not be submitted.

```php
add_action( 'super_before_sending_email_hook', 'f4d_compare_database_value', 1 );
function f4d_compare_database_value($x){
    extract(shortcode_atts(array('data'=>array(), 'form_id'=>0), $x));
    $your_form_id = 12345;
    if($form_id!==$your_form_id) return;
    $data = wp_unslash($data);
    // Our form has a field named `code` and we will lookup if this code exists in our custom databaset table named `wp_codes`
    // Make sure to trim whitespaces at the start and end of the string
    $code = trim(sanitize_text_field($data['code']['value']));
    // Table structure that is used in this example:
    // CREATE TABLE `db_name`.`wp_codes` (`id` INT NOT NULL AUTO_INCREMENT , `code` VARCHAR(50) NOT NULL , PRIMARY KEY (`id`), UNIQUE (`code`)) ENGINE = InnoDB;
    global $wpdb;
    $codesTable = $wpdb->prefix.'codes';
    $sql = $wpdb->prepare("SELECT COUNT(*) FROM $codesTable AS c WHERE c.code = '%s'", $code);
    $found = $wpdb->get_var($sql);
    if(absint($found)===0){
        // Code does not exists, return error
        echo json_encode(
            array(
                'error' => true,
                'fields' => array('code'), // field that contains errors
                'msg' => 'Entered code is invalid, please try again'
            )
        );
        die();
    }
}
```
