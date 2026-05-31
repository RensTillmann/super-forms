# Hook Example Code

**Examples:**

- [Track form submissions with GTM (Google Tag Manager)](#track-form-submissions-with-gtm-google-tag-manager)
- [Track form submissions with third party](#track-form-submissions-with-third-party)
- [Insert form data into a custom database table](#insert-form-data-into-a-custom-database-table)
- [Send submitted form data to another site](#send-submitted-form-data-to-another-site)
- [Exclude empty fields from emails](#exclude-empty-fields-from-emails)
- [Execute custom JS when a column becomes conditionally visible](#execute-custom-js-when-a-column-becomes-conditionally-visible)
- [Toolset Plugin: Update comma seperated string to Array for meta data saved via Front-end Posting](#toolset-plugin-update-comma-seperated-string-to-array-for-meta-data-saved-via-front-end-posting)
- [Toolset Plugin: Update file ID to file URL for meta data saved via Front-end Posting](#toolset-plugin-update-file-id-to-file-url-for-meta-data-saved-via-front-end-posting)
- [Delete uploaded files after email has been send](#delete-uploaded-files-after-email-has-been-send)
- [Increase Cookie lifetime for client data such as form progression](#increase-cookie-lifetime-for-client-data-such-as-form-progression)
- [Altering cookie secure and httponly parameters](#altering-cookie-secure-and-httponly-parameters)
- [Define fake cronjob to clear old client data if cronjob is disabled on your server](#define-fake-cronjob-to-clear-old-client-data-if-cronjob-is-disabled-on-your-server)

## Track form submissions with GTM (Google Tag Manager)

_Insert the below PHP code in your child theme functions.php file, or create a custom plugin. You may also use a plugin that allows you to insert code snippets to your site._

```php
add_action('wp_footer', function(){
    ?>
    <script type="text/javascript">
        // Execute after form submission
        if(typeof SUPER === 'undefined') {
            // Custom JS script was loaded to early
            window.SUPER = {};
        }
        SUPER.custom_form_tracker = function(args){
            // Grab form fields
            var product_name= (args.data.product_name? args.data.product_name.value : '');
            var quantity= (args.data.quantity? args.data.quantity.value : '');
            var total= (args.data.total? args.data.total.value : '');
            var utm_source= (args.data.utm_source? args.data.utm_source.value : ''); 
            // Your third party code here
            window.dataLayer = window.dataLayer || []
            dataLayer.push({
                'event': 'superFormsSubmission',
                'formID': args.form_id,
                'product_name': product_name,
                'quantity' : quantity,
                'total' : total,
                'utm_source': utm_source
            });
        }
    </script>
    <?php
}, 100);

// Add custom javascript function 
function f4d_add_dynamic_function( $functions ) {
    $functions['after_email_send_hook'][] = array(
        'name' => 'custom_form_tracker'
    );
    return $functions;
}
add_filter( 'super_common_js_dynamic_functions_filter', 'f4d_add_dynamic_function', 100, 2 );
```

## Track form submissions with third party

PHP code:

```php
    // Load f4d-custom.js
    function f4d_enqueue_script() {
        wp_enqueue_script( 'f4d-custom', plugin_dir_url( __FILE__ ) . 'f4d-custom.js', array( 'super-common' ) );
    }
    add_action( 'wp_enqueue_scripts', 'f4d_enqueue_script' );
    add_action( 'admin_enqueue_scripts', 'f4d_enqueue_script' );

    // Add custom javascript function
    function f4d_add_dynamic_function( $functions ) {
        $functions['after_email_send_hook'][] = array(
            'name' => 'after_form_submission'
        );
        return $functions;
    }
    add_filter( 'super_common_js_dynamic_functions_filter', 'f4d_add_dynamic_function', 100, 2 );
```

JS script (f4d-custom.js)

```js
    // Execute after form submission
    SUPER.after_form_submission = function($form){
        // Your third party code here
        alert('Your third party code here');
    }
```

## Insert form data into a custom database table

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
            $wpdb->insert( $table, $values );
        }
    }
```

## Send submitted form data to another site

With the below example code you can send the submitted form data to a different site.

```php
    add_action('super_before_email_success_msg_action', '_super_submit_data_to_site', 10, 1);
    function _super_submit_data_to_site( $atts ) {

        // CHANGE THE BELOW 2 VARIABLES ACCORDINGLY
        $url = 'http://example.com'; // change this URL accordingly
        $id = 123; // replace 123 with the ID of the form

        // CHANGE THE BELOW ARRAY AND ADD FIELDS ACCORDINGLY
        $fields = array(
            'first_name', // replace first_name with the appropriate field name from your form
            'last_name', // replace last_name with the appropriate field name from your form
            'field_name1', // add your own fields to this array to send them to your site
            'field_name2', // add your own fields to this array to send them to your site
            'field_name3', // add your own fields to this array to send them to your site
            // etc...
        );

        $form_id = absint($atts['post']['form_id']); // contains the form ID that was submitted
        if( $form_id == $id ) {
            $data = $atts['data']; // contains the submitted form data
            $args = array();

            // Add field values to arguments
            foreach( $fields as $k ) {
                if( isset( $data[$k]['value'] ) ) {
                      $args[$k] = $data[$k]['value'];
                }
            }

            // Send the request
            $response = wp_remote_post( $url, array('body' => $args));

            // Output error message if any
            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                SUPER_Common::output_message(
                    $error = true,
                    $msg = $error_message
                );
            }

        }
    }
```

## Exclude empty fields from emails

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

## Execute custom JS when a column becomes conditionally visible

In some cases you might want to trigger or execute some custom JavaScript upon a column becoming conditionally visible.
This is possible with just a little code. In this example we have added a custom class to the column named `f4d-column-script`. This way we can identify the column and do a check on it wether it became visible at a specific point. As soon as it becomes visible to the user it executes the custom javascript as seen below. Just replace `YOUR CUSTOM JAVASCRIPT GOES HERE` with your JS.

```js
// Check every 100ms and figure out if the column became visible or not
setInterval(function(){
    var column = document.querySelector('.f4d-column-script');
    if(column){
        // First check if it has any of the classes
        if(column.style.display=='block'){
            if(!column.classList.contains('super-custom-js-executed')){
                column.classList.add('super-custom-js-executed');
                // YOUR CUSTOM JAVASCRIPT GOES HERE
            }
        }else{
            // do nothing
            column.classList.remove('super-custom-js-executed');
        }
    }
},100);
```

## Toolset Plugin: Update comma seperated string to Array for meta data saved via Front-end Posting

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

## Toolset Plugin: Update file ID to file URL for meta data saved via Front-end Posting

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

## Delete uploaded files after email has been send

?> **NOTE:** This code should no longer be used if you wish to delete all the files, since you can do that via `Super Forms > Settings > File Upload Settings`.

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

## Increase Cookie lifetime for client data such as `Form Progression`

?> **NOTE:** These filter hooks are available since __Super Forms v6.3+__. Always set the cookie lifetime to a higher value than the client data lifetime, for instance if you set the cookie expiry to 10 hours, your client data can't exceed 10 hours simply because the session would already be deleted along with any data. This allows you to define specific client data with a shorter lifetime than others, while having a single session ID for the current client.

```php
function f4d_super_cookie_expires_filter($expires) {
    // Please note: this lifetime must be higher than what you set for `super_client_data_expires_filter`
    return 60*60; // Increase or decrease the session lifetime, by default the expiry is set to 1 hour (3600) or (60*60)
}
add_filter( 'super_cookie_expires_filter', 'f4d_super_cookie_expires_filter' );

function f4d_super_cookie_exp_var_filter($exp_var) {
    return 20*60; // Increase or decrease the session update/extend lifetime, by default the expiry is set to 20 min. (1200) or (20*60)
}
add_filter( 'super_cookie_exp_var_filter', 'f4d_super_cookie_exp_var_filter' );

function f4d_super_client_data_expires_filter($expires) {
    // Please note: this lifetime must be lower than what you set for `super_cookie_expires_filter`
    return 30*60; // Increase or decrease the client data lifetime, by default the expiry is set to 30 min. (1800) or (30*60)
}
add_filter( 'super_client_data_expires_filter', 'f4d_super_client_data_expires_filter' );

function f4d_super_client_data_exp_var_filter($exp_var) {
    return 10*60; // Increase or decrease the client data update/extend lifetime, by default the expiry is set to 10 min. (600) or (10*60)
}
add_filter( 'super_client_data_exp_var_filter', 'f4d_super_client_data_exp_var_filter' );

// Filtering lifetime for specific client data is also possible, simply replace $name with the data that is being saved, for instance `progress_1234` where 1234 would be your form ID
add_filter( 'super_client_data_$name_expires_filter', 'f4d_super_client_data_expires_filter' );
add_filter( 'super_client_data_$name_exp_var_filter', 'f4d_super_client_data_exp_var_filter' );
```

## Altering cookie secure and httponly parameters

?> **NOTE:** If needed you can change how the cookie is being stored, by default when is_ssl() returns true, a secure cookie will be stored. The cookie HttpOnly parameter is always set to true unless you define otherwise

```php
function f4d_super_cookie_secure_filter($secure) {
    return true; // set to false to disable
}
add_filter( 'super_cookie_secure_filter', 'f4d_super_cookie_secure_filter' );

function f4d_super_cookie_httponly_filter($httponly) {
    return false; // set HttpOnly parameter for the cookie to false, by default this is set to true
}
add_filter( 'super_cookie_httponly_filter', 'f4d_super_cookie_httponly_filter' );

function f4d_super_client_data_delete_limit_filter($limit) {
    return 10; // Maximum items to delete per query when cleaning up old client data (by default this value is set to 10)
}
add_filter( 'super_client_data_delete_limit_filter', 'f4d_super_client_data_delete_limit_filter' );
```

## Define fake cronjob to clear old client data if cronjob is disabled on your server

These hooks don't need to be changed in normal circumstances, only use these if you know what you are doing.
By default Super Forms will clean up expired client data each 1 out of 50 requests, if you wish to increase this you can increase it to 500 or 9999 as an example.

```php
function f4d_super_delete_old_client_data_manually_interval_filter($limit) {
    return 50; // Trigger the function to delete old client data roughly 1 out of 50 requests
}
add_filter( 'super_delete_old_client_data_manually_interval_filter', 'f4d_super_delete_old_client_data_manually_interval_filter' );

function f4d_super_delete_client_data_manually_limit_filter($limit) {
    return 10; // When deleting client data via manual request, we only want to delete 10 items at a time
}
add_filter( 'super_delete_client_data_manually_limit_filter', 'f4d_super_delete_client_data_manually_limit_filter' );
```
