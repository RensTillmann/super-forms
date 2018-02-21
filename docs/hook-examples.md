# Hook Example Code

**Examples:**
- [Insert form data into a custom database table](#insert-form-data-into-a-custom-database-table)
- [Send submitted form data to another site](#send-submitted-form-data-to-another-site)
- [Delete uploaded files after email has been send](#delete-uploaded-files-after-email-has-been-send)

### Insert form data into a custom database table

	add_action('super_before_email_success_msg_action', 'your_custom_function', 10, 1);
	function copy_into_my_table( $atts ) {
		
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
				$values[$k] = $v;
			}
			$wpdb->insert( $table, $values );
		}
	}


### Send submitted form data to another site

With the below example code you can send the submitted form data to a different site.
	
	add_action('super_before_email_success_msg_action', 'your_custom_function', 10, 1);
	function your_custom_function( $atts ) {
	
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
	    	$result = wp_remote_post( $url, array('body' => $args));
	    }
	}

### Delete uploaded files after email has been send
	
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
