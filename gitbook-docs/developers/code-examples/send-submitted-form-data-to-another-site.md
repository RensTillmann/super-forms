---
description: Sending your WordPress submitted form data to another website.
---

# Send submitted form data to another site

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
                SUPER_Common::output_message( array(
                    'error' => true,
                    'msg' => $error_message
                ));
            }

        }
    }
```
