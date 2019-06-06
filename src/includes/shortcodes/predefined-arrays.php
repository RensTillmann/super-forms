<?php
$name = array(
    'name'=>__( 'Name', 'super-forms' ), 
    'desc'=>__( 'Unique field name (required)', 'super-forms' ),
    'default'=> (!isset($attributes['name']) ? '' : $attributes['name']),
    'required'=>true, 
);
$email = array(
    'name'=>__( 'E-mail & Contact Entry Label', 'super-forms' ), 
    'label' => __( 'When using dynamic columns, you can use %d to determine where the counter should be placed e.g: "Product %d quantity:" would be converted into "Product 3 quantity:"', 'super-forms' ),
    'desc'=>__( 'Indicates the field in emails and contact entries. (required)', 'super-forms' ),
    'default'=> (!isset($attributes['email']) ? '' : $attributes['email']),
    'required'=>true,
    'i18n' => true
);
$label = array(
    'name'=>__( 'Field Label', 'super-forms' ), 
    'desc'=>__( 'Will be visible in front of your field. (leave blank to remove)', 'super-forms' ),
    'default'=> (!isset($attributes['label']) ? '' : $attributes['label']),
    'i18n' => true
);
$description = array(
    'name'=>__( 'Field description', 'super-forms' ), 
    'desc'=>__( 'Will be visible in front of your field. (leave blank to remove)', 'super-forms' ),
    'default'=> (!isset($attributes['description']) ? '' : $attributes['description']),
    'i18n' => true
);
$tooltip = array(
    'default'=> (!isset($attributes['tooltip']) ? '' : $attributes['tooltip']),
    'name'=>__( 'Tooltip text', 'super-forms' ), 
    'desc'=>__( 'The tooltip will appear as soon as the user hovers over the field with their mouse.', 'super-forms' ),
    'i18n' => true
);        
$extensions = array(
    'default'=> (!isset($attributes['extensions']) ? 'jpg|jpeg|png|gif|pdf' : $attributes['extensions']),
    'type' => 'textarea', 
    'name' => __( 'Allowed Extensions (seperated by pipes)', 'super-forms' ),
    'desc' => __( 'Example', 'super-forms' ).': jpg|jpeg|png|gif|pdf'
);
$special_validations = array(
    'name'=>__( 'Special Validation', 'super-forms' ), 
    'desc'=>__( 'How does this field need to be validated?', 'super-forms' ), 
    'default'=> (!isset($attributes['validation']) ? 'none' : $attributes['validation']),
    'type'=>'select',
    'values'=>array(
        'none' => __( 'No validation needed', 'super-forms' ),
        'empty' => __( 'Not empty', 'super-forms' ), 
        'email' => __( 'Email address', 'super-forms' ), 
        'phone' => __( 'Phone number', 'super-forms' ), 
        'numeric' => __( 'Numeric', 'super-forms' ),
        'float' => __( 'Float', 'super-forms' ),
        'website' => __( 'Website URL', 'super-forms' ),
        'iban' => __( 'IBAN', 'super-forms' ),
        'custom' => __( 'Custom Regex', 'super-forms' ),
    ),
    'filter'=>true
);
$custom_regex = array(
    'default'=> (!isset($attributes['custom_regex']) ? '' : $attributes['custom_regex']),
    'name'=>__( 'Custom Regex', 'super-forms' ), 
    'desc'=>__( 'Use your own custom regex to validate this field', 'super-forms' ),
    'filter'=>true,
    'parent'=>'validation',
    'filter_value'=>'custom'
);
$may_be_empty = array(
    'name'=>__( 'Allow field to be empty', 'super-forms' ), 
    'desc'=>__( 'Only apply the validations if field is not empty', 'super-forms' ), 
    'default'=> (!isset($attributes['may_be_empty']) ? 'false' : $attributes['may_be_empty']),
    'type'=>'select', 
    'values'=>array(
        'false' => __( 'No, validate even if field is empty (default)', 'super-forms' ), 
        'true' => __( 'Yes, validate only if field is not empty', 'super-forms' ),
    )
);

// @since   1.0.6
$conditional_validation = array(
    'name'=>__( 'Conditional Validation', 'super-forms' ), 
    'desc'=>__( 'Add some extra validation for this field', 'super-forms' ), 
    'default'=> (!isset($attributes['conditional_validation']) ? 'none' : $attributes['conditional_validation']),
    'type'=>'select', 
    'filter'=>true,
    'values'=>array(
        'none' => __( 'No validation needed', 'super-forms' ),
        'contains' => __( '?? Contains', 'super-forms' ),
        'equal' => __( '== Equal', 'super-forms' ),
        'not_equal' => __( '!= Not equal', 'super-forms' ),
        'greater_than' => __( '&gt; Greater than', 'super-forms' ),
        'less_than' => __( '&lt;  Less than', 'super-forms' ),
        'greater_than_or_equal' => __( '&gt;= Greater than or equal to', 'super-forms' ),
        'less_than_or_equal' => __( '&lt;= Less than or equal', 'super-forms' ),

        // @since 3.6.0 - more specific conditional validation options
        // > && <
        // > || <
        'greater_than_and_less_than' => __( '&gt; && &lt; Greater than AND Less than', 'super-forms' ),
        'greater_than_or_less_than' => __( '&gt; || &lt; Greater than OR Less than', 'super-forms' ),
        // >= && <
        // >= || <
        'greater_than_or_equal_and_less_than' => __( '&gt;= && &lt; Greater than or equal to AND Less than', 'super-forms' ),
        'greater_than_or_equal_or_less_than' => __( '&gt;= || &lt; Greater than or equal to OR Less than', 'super-forms' ),
        // > && <=
        // > || <=
        'greater_than_and_less_than_or_equal' => __( '&gt; && &lt;= Greater than AND Less than or equal to', 'super-forms' ),
        'greater_than_or_less_than_or_equal' => __( '&gt; || &lt;= Greater than OR Less than or equal to', 'super-forms' ),
        // >= && <=
        // >= || <=
        'greater_than_or_equal_and_less_than_or_equal' => __( '&gt;= && &lt;= Greater than or equal to AND Less than or equal to', 'super-forms' ),
        'greater_than_or_equal_or_less_than_or_equal' => __( '&gt;= || &lt;= Greater than or equal to OR Less than or equal to', 'super-forms' ),
    )
);
$conditional_validation_value = array(
    'name'=>__( 'Conditional Validation Value', 'super-forms' ), 
    'desc'=>__( 'Enter the value you want to validate', 'super-forms' ), 
    'default'=> (!isset($attributes['conditional_validation_value']) ? '' : $attributes['conditional_validation_value']),
    'filter'=>true,
    'parent'=>'conditional_validation',
    'filter_value'=>'contains,equal,not_equal,greater_than,less_than,greater_than_or_equal,less_than_or_equal,greater_than_and_less_than,greater_than_or_less_than,greater_than_or_equal_and_less_than,greater_than_or_equal_or_less_than,greater_than_and_less_than_or_equal,greater_than_or_less_than_or_equal,greater_than_or_equal_and_less_than_or_equal,greater_than_or_equal_or_less_than_or_equal'
);
$conditional_validation_value2 = array(
    'name'=>__( 'Conditional Validation Value 2', 'super-forms' ), 
    'desc'=>__( 'Enter the second value you want to validate', 'super-forms' ), 
    'default'=> (!isset($attributes['conditional_validation_value2']) ? '' : $attributes['conditional_validation_value2']),
    'filter'=>true,
    'parent'=>'conditional_validation',
    'filter_value'=>'greater_than_and_less_than,greater_than_or_less_than,greater_than_or_equal_and_less_than,greater_than_or_equal_or_less_than,greater_than_and_less_than_or_equal,greater_than_or_less_than_or_equal,greater_than_or_equal_and_less_than_or_equal,greater_than_or_equal_or_less_than_or_equal'
);

$validation_empty = array(
    'name'=>__( 'Validation', 'super-forms' ), 
    'desc'=>__( 'How does this field need to be validated?', 'super-forms' ), 
    'default'=> (!isset($attributes['validation']) ? 'none' : $attributes['validation']),
    'type'=>'select', 
    'values'=>array(
        'none' => __( 'No validation needed', 'super-forms' ), 
        'empty' => __( 'Not empty', 'super-forms' )
    )
);
$validation_not_empty = array(
    'name'=>__( 'Validation', 'super-forms' ), 
    'desc'=>__( 'How does this field need to be validated?', 'super-forms' ), 
    'default'=> (!isset($attributes['validation']) ? 'empty' : $attributes['validation']),
    'type'=>'select', 
    'values'=>array(
        'none' => __( 'No validation needed', 'super-forms' ), 
        'empty' => __( 'Not empty', 'super-forms' )
    )
);
$error = array(
    'default'=> (!isset($attributes['error']) ? '' : $attributes['error']),
    'name'=>__( 'Error Message', 'super-forms' ), 
    'desc'=>__( 'A message to show up when field was filled out incorrectly.', 'super-forms' ),
    'i18n' => true
);  
$grouped = array(
    'name' => __( 'Individual / Grouped', 'super-forms' ), 
    'desc' => __( 'Select grouped, if you wish to append the field next to it\'s previous field.', 'super-forms' ), 
    'default'=> (!isset($attributes['grouped']) ? 0 : $attributes['grouped']),
    'type' => 'select', 
    'values' => array(
        '0' => __( 'Individual field', 'super-forms' ), 
        '1' => __( 'Grouped field', 'super-forms' ),
        '2' => __( 'Last Grouped field (closes/ends a group)', 'super-forms' )
    )
);
$disabled = array(
    'name' => __( 'Disable the input field', 'super-forms' ), 
    'desc' => __( 'Make this field disabled, this way a user cannot edit the field value', 'super-forms' ), 
    'default'=> (!isset($attributes['disabled']) ? '' : $attributes['disabled']),
    'type' => 'select', 
    'values' => array(
        '' => __( 'No (users can edit the value)', 'super-forms' ), 
        '1' => __( 'Yes (users can\'t edit the value)', 'super-forms' ), 
    )
);
$readonly = array(
    'name' => __( 'Make field read-only', 'super-forms' ), 
    'desc' => __( 'A read-only input field cannot be modified (however, a user can tab to it, highlight it, and copy the text from it).', 'super-forms' ), 
    'default'=> (!isset($attributes['readonly']) ? '' : $attributes['readonly']),
    'type' => 'checkbox', 
    'values' => array(
        'true' => __( 'Enable read-only', 'super-forms' ), 
    )
);
$autocomplete = array(
    'desc' => __( 'This will prevent browser from automatically autopopulating a field when user starts typing with previously submitted data', 'super-forms' ), 
    'default'=> (!isset($attributes['autocomplete']) ? '' : $attributes['autocomplete']),
    'type' => 'checkbox', 
    'values' => array(
        'true' => __( 'Disable autocompletion', 'super-forms' ), 
    )
);
$maxlength = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['maxlength']) ? 0 : $attributes['maxlength']),
    'min' => 0, 
    'max' => 100, 
    'steps' => 1, 
    'name' => __( 'Max characters/selections allowed', 'super-forms' ), 
    'desc' => __( 'Set to 0 to remove limitations.', 'super-forms' )
);
$minlength = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['minlength']) ? 0 : $attributes['minlength']),
    'min' => 0, 
    'max' => 100, 
    'steps' => 1, 
    'name' => __( 'Min characters/selections allowed', 'super-forms' ), 
    'desc' => __( 'Set to 0 to remove limitations.', 'super-forms' )
);
$maxnumber = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['maxnumber']) ? 0 : $attributes['maxnumber']),
    'min' => 0, 
    'max' => 100, 
    'steps' => 1, 
    'name' => __( 'Max number allowed', 'super-forms' ), 
    'desc' => __( 'Set to 0 to remove limitations.', 'super-forms' )
);
$minnumber = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['minnumber']) ? 0 : $attributes['minnumber']),
    'min' => 0, 
    'max' => 100, 
    'steps' => 1, 
    'name' => __( 'Min number allowed', 'super-forms' ), 
    'desc' => __( 'Set to 0 to remove limitations.', 'super-forms' )
);
$width = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['width']) ? 0 : $attributes['width']),
    'min' => 0, 
    'max' => 600, 
    'steps' => 10, 
    'name' => __( 'Field width in pixels', 'super-forms' ), 
    'desc' => __( 'Set to 0 to use default CSS width.', 'super-forms' )
);
$wrapper_width = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['wrapper_width']) ? 0 : $attributes['wrapper_width']),
    'min' => 0, 
    'max' => 600, 
    'steps' => 10, 
    'name' => __( 'Wrapper width in pixels', 'super-forms' ), 
    'desc' => __( 'Set to 0 to use default CSS width.', 'super-forms' )
);
$height = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['height']) ? 0 : $attributes['height']),
    'min' => 0, 
    'max' => 600, 
    'steps' => 10, 
    'name' => __( 'Field height in pixels', 'super-forms' ), 
    'desc' => __( 'Set to 0 to use default CSS height.', 'super-forms' )
);
$exclude = array(
    'name'=>__( 'Exclude from email', 'super-forms' ), 
    'desc'=>__( 'You can prevent this data from being send to the form submitter (if the option to send confirmation email has been enabled).', 'super-forms' ), 
    'default'=> (!isset($attributes['exclude']) ? 0 : $attributes['exclude']),
    'type'=>'select', 
    'values'=>array(
        '0'=>__( 'Do not exclude from emails', 'super-forms' ),
        '1'=>__( 'Exclude from confirmation email', 'super-forms' ), 

        // Since version 1.0.4
        '2'=>__( 'Exclude from all emails', 'super-forms' )
    )
);

// @since 3.9.0 - replace comma's with specific html tag in emails for radio/checkbox/dropdown elements
$replace_commas = array(
    'name'=>__( 'Replace comma\'s with HTML tag in emails', 'super-forms' ), 
    'label'=>__( 'With this setting you can use &lt;br /&gt; to use line breaks for each selected option instead of a comma seperated list. Of course you can also use other HTML if necessary.', 'super-forms' ), 
    'desc'=>__( 'Leave empty for no replacement', 'super-forms' ), 
    'default'=> (!isset($attributes['replace_commas']) ? '' : $attributes['replace_commas']),
);

// @since 3.3.0
$exclude_entry = array(
    'desc'=>__( 'Wether or not to prevent this field from being saved in Contact Entry.', 'super-forms' ), 
    'default'=> ( !isset( $attributes['exclude_entry'] ) ? '' : $attributes['exclude_entry'] ),
    'type' => 'checkbox', 
    'values' => array(
        'true' => __( 'Do not save field in Contact Entry', 'super-forms' ),
    )
);

// @since 1.2.7
$admin_email_value = array(
    'name'=>__( 'Send value or label to admin?', 'super-forms' ), 
    'desc'=>__( 'Choose to send only the selected option label or value to the admin', 'super-forms' ), 
    'default'=> (!isset($attributes['admin_email_value']) ? 'value' : $attributes['admin_email_value']),
    'type'=>'select', 
    'values'=>array(
        'value'=>__( 'Only send the value', 'super-forms' ),
        'label'=>__( 'Only send the label', 'super-forms' ),
        'both'=>__( 'Send both value and label', 'super-forms' ),
    )
);
$confirm_email_value = array(
    'name'=>__( 'Send value or label to submitter?', 'super-forms' ), 
    'desc'=>__( 'Choose to send only the selected option label or value to the submitter', 'super-forms' ), 
    'default'=> (!isset($attributes['confirm_email_value']) ? 'value' : $attributes['confirm_email_value']),
    'type'=>'select', 
    'values'=>array(
        'value'=>__( 'Only send the value', 'super-forms' ),
        'label'=>__( 'Only send the label', 'super-forms' ),
        'both'=>__( 'Send both value and label', 'super-forms' ),
    )
);

// @since 1.2.9
$contact_entry_value = array(
    'name'=>__( 'Save value or label to entry?', 'super-forms' ), 
    'desc'=>__( 'Choose to save only the selected option label or value as contact entry', 'super-forms' ), 
    'default'=> (!isset($attributes['contact_entry_value']) ? 'value' : $attributes['contact_entry_value']),
    'type'=>'select', 
    'values'=>array(
        'value'=>__( 'Only save the value', 'super-forms' ),
        'label'=>__( 'Only save the label', 'super-forms' ),
        'both'=>__( 'Save both value and label', 'super-forms' ),
    )
);

$error_position = array(
    'name'=>__( 'Error message positioning', 'super-forms' ), 
    'default'=> (!isset($attributes['error_position']) ? '' : $attributes['error_position']),
    'type'=>'select', 
    'values'=>array(
        ''=>__( 'Default positioning (bottom right)', 'super-forms' ), 
        'bottom-right'=>__( 'Bottom right', 'super-forms' ), 
        'bottom-left'=>__( 'Bottom left', 'super-forms' ), 
        'top-right'=>__( 'Top right', 'super-forms' ), 
        'top-left'=>__( 'Top left', 'super-forms' ), 
    )
); 
$error_position_left_only = array(
    'name'=>__( 'Error message positioning', 'super-forms' ), 
    'default'=> (!isset($attributes['error_position']) ? '' : $attributes['error_position']),
    'type'=>'select', 
    'values'=>array(
        ''=>__( 'Default positioning', 'super-forms' ), 
        'bottom-left'=>__( 'Bottom left', 'super-forms' ), 
        'top-left'=>__( 'Top left', 'super-forms' ), 
    )
);         
$styles = array(
    'type' => 'textarea', 
    'default'=> (!isset($attributes['styles']) ? '' : $attributes['styles']),
    'name' => __( 'Extra styles', 'super-forms' ), 
    'desc' => __( 'Use this to add some extra styles for this element.', 'super-forms' ),
);
$conditional_field_name = array(
    'name'=>__( 'Retrieve value from', 'super-forms' ), 
    'desc'=>__( 'Based on the above selected field value this element will be vissible or hidden.', 'super-forms' ),
    'type' => 'previously_created_fields',
    'default'=> (!isset($attributes['conditional_field']) ? '' : $attributes['conditional_field']),
    'values' => array(
        '' => '- select a field -',
    ),
);        
$conditional_logic = array(
    'default'=> (!isset($attributes['logic']) ? '' : $attributes['logic']),
    'name'=>__( 'Conditional logic', 'super-forms' ), 
    'desc'=>__( 'The logic/method of the validation.', 'super-forms' ),
    'type'=>'select',
    'values'=> array(
        'contains'=>'?? '.__( 'Contains', 'super-forms' ),
        'equal'=>'== '.__( 'Equal', 'super-forms' ),
        'not_equal'=>'!= '.__( 'Not equal', 'super-forms' ),
        'greater_than'=>'> '.__( 'Greater than', 'super-forms' ),
        'less_than'=>'<  '.__( 'Less than', 'super-forms' ),
        'greater_than_or_equal'=>'>= '.__( 'Greater than or equal to', 'super-forms' ),
        'less_than_or_equal'=>'<= '.__( 'Less than or equal to', 'super-forms' ),
    ),
);
$conditional_field_value = array(
    'default'=> (!isset($attributes['value']) ? '' : $attributes['value']),
    'name'=>__( 'Conditional field value', 'super-forms' ), 
    'desc'=>__( 'The value the field needs to have before this field will become visible.', 'super-forms' )
);
$animation = array(
    '' => __( 'No animation', 'super-forms' ),
    'fade-in' => __( 'Fade in without movement', 'super-forms' ), 
    'fade-in-up' => __( 'Fade in down to up', 'super-forms' ), 
    'fade-in-left' => __( 'Fade in right to left', 'super-forms' ),
    'fade-in-right' => __( 'Fade in left to right', 'super-forms' ),
    'fade-in-down' => __( 'Fade in up to down', 'super-forms' )
);
$icon_position = array(
    'default'=> (!isset($attributes['icon_position']) ? 'outside' : $attributes['icon_position']),
    'name'=>__( 'Icon positioning', 'super-forms' ), 
    'desc'=>__( 'How to display your icon.', 'super-forms' ),
    'type'=>'select',
    'values'=> array(
        'inside'=>__( 'Inside the field', 'super-forms' ),
        'outside'=>__( 'Outside the field', 'super-forms' ),
    ),
);
$icon_align = array(
    'default'=> (!isset($attributes['icon_align']) ? 'left' : $attributes['icon_align']),
    'name'=>__( 'Icon alignment', 'super-forms' ), 
    'desc'=>__( 'Align icon to the left or right.', 'super-forms' ),
    'type'=>'select',
    'values'=> array(
        'left'=>__( 'Left', 'super-forms' ),
        'right'=>__( 'Right', 'super-forms' ),
    ),
);
$icon = array(
    'default'=> (!isset($attributes['icon']) ? 'user' : $attributes['icon']),
    'name'=>__( 'Select an Icon', 'super-forms' ), 
    'type'=>'icon',
    'desc'=>__( 'Leave blank if you prefer to not use an icon.', 'super-forms' )
);

$conditional_action = array(
    'name'=>__( 'Show or Hide?', 'super-forms' ), 
    'desc'=>__( 'Based on your conditions you can choose to hide or show this field.', 'super-forms' ), 
    'default'=> (!isset($attributes['conditional_action']) ? 'disabled' : $attributes['conditional_action']),
    'type'=>'select',
    'values'=>array(
        'disabled'=>__( 'Disabled (do not use conditional logic)', 'super-forms' ),
        'show'=>__( 'Show', 'super-forms' ),
        'hide'=>__( 'Hide', 'super-forms' ),
    ),
    'filter'=>true,
);
$conditional_trigger = array(
    'name'=>__( 'When to Trigger?', 'super-forms' ), 
    'desc'=>__( 'Trigger only when all or one of the below conditions matched their value.', 'super-forms' ), 
    'default'=> (!isset($attributes['conditional_trigger']) ? 'all' : $attributes['conditional_trigger']),
    'type'=>'select',
    'values'=>array(
        'all'=>__( 'All (when all conditions matched)', 'super-forms' ),
        'one'=>__( 'One (when one condition matched)', 'super-forms' ),
    ),
    'filter'=>true,
    'parent'=>'conditional_action',
    'filter_value'=>'show,hide'
);
$conditional_logic_array = array(
    'name' => __( 'Conditional Logic', 'super-forms' ),
    'fields' => array(
        'conditional_action' => $conditional_action,
        'conditional_trigger' => $conditional_trigger,
        'conditional_items' => array( 
            'name'=>__( 'Conditions', 'super-forms' ), 
            'desc'=>__( 'The conditions that this element should listen to.', 'super-forms' ),
            'type'=>'conditions',
            'default'=> (!isset($attributes['conditional_items']) ? '' : $attributes['conditional_items']),
            'filter'=>true,
            'parent'=>'conditional_action',
            'filter_value'=>'show,hide' 
        )
    )   
);

// @since 1.2.7
$conditional_variable_array = array(
    'name' => __( 'Conditional Variable (dynamic value)', 'super-forms' ),
    'fields' => array(
        'conditional_variable_action' => array(
            'name'=>__( 'Make field variable', 'super-forms' ), 
            'desc'=>__( 'Choose to make this field a variable or not.', 'super-forms' ), 
            'default'=> (!isset($attributes['conditional_variable_action']) ? 'disabled' : $attributes['conditional_variable_action']),
            'type'=>'select',
            'values'=>array(
                'disabled'=>__( 'Disabled (do not make variable)', 'super-forms' ),
                'enabled'=>__( 'Enabled (make variable)', 'super-forms' ),
            ),
            'filter'=>true,
        ),
        
        // @since 4.2.0 - allow to retrieve conditions via CSV files
        'conditional_variable_method' => array(
            'name'=>__( 'Retrieve method', 'super-forms' ),
            'desc'=>__( 'Select how you would want to define the conditions (manually or via a CSV file)', 'super-forms' ), 
            'default'=> (!isset($attributes['conditional_variable_method']) ? 'manual' : $attributes['conditional_variable_method']),
            'type'=>'select',
            'values'=>array(
                'manual'=>__( 'Manually enter each condition (default)', 'super-forms' ),
                'csv'=>__( 'CSV file', 'super-forms' ),
            ),
            'filter'=>true,
            'parent'=>'conditional_variable_action',
            'filter_value'=>'enabled'
        ),
        'conditional_variable_csv' => array(
            'name' => __( 'Upload CSV file', 'super-forms' ), 
            'label' => sprintf( __( 'Please read the %sCreating variable conditions with CSV file%s section in the documentation before using this method.', 'super-forms' ), '<a target="_blank" href="https://renstillmann.github.io/super-forms/#/variable-fields?id=creating-variable-conditions-with-csv-file">', '</a>' ),
            'default'=> ( !isset( $attributes['conditional_variable_csv'] ) ? '' : $attributes['conditional_variable_csv'] ),
            'type' => 'file',
            'filter'=>true,
            'parent'=>'conditional_variable_method',
            'filter_value'=>'csv',
            'file_type'=>'text/csv'
        ),
        'conditional_variable_row' => array(
            'name' => __( 'Row heading', 'super-forms' ), 
            'default'=> ( !isset( $attributes['conditional_variable_row'] ) ? '' : $attributes['conditional_variable_row'] ),
            'type' => 'previously_created_fields',
            'values' => array(
                '' => '- select a field -',
            ),
            'filter'=>true,
            'parent'=>'conditional_variable_method',
            'filter_value'=>'csv'  
        ),
        'conditional_variable_logic' => array(
            'name' => __( 'Row logic', 'super-forms' ), 
            'default'=> ( !isset( $attributes['conditional_variable_logic'] ) ? '' : $attributes['conditional_variable_logic'] ),
            'type' => 'select',
            'values' => array(
                ''=>'- select -',
                'contains'=>'?? Contains',
                'equal'=>'== Equal',
                'not_equal'=>'!= Not equal',
                'greater_than'=>'> Greater than',
                'less_than'=>'<  Less than',
                'greater_than_or_equal'=>'>= Greater than or equal to',
                'less_than_or_equal'=>'<= Less than or equal',
            ),
            'filter'=>true,
            'parent'=>'conditional_variable_method',
            'filter_value'=>'csv'  
        ),
        'conditional_variable_and_method' => array(
            'name' => __( 'Compare method (OR / AND)', 'super-forms' ), 
            'default'=> ( !isset( $attributes['conditional_variable_and_method'] ) ? '' : $attributes['conditional_variable_and_method'] ),
            'type' => 'select',
            'values' => array(
                ''=>'- select -',
                'and'=>'AND',
                'or'=>'OR',
            ),
            'filter'=>true,
            'parent'=>'conditional_variable_method',
            'filter_value'=>'csv'  
        ),
        'conditional_variable_col' => array(
            'name' => __( 'Column heading', 'super-forms' ), 
            'default'=> ( !isset( $attributes['conditional_variable_col'] ) ? '' : $attributes['conditional_variable_col'] ),
            'type' => 'previously_created_fields',
            'values' => array(
                '' => '- select a field -',
            ),
            'filter'=>true,
            'parent'=>'conditional_variable_and_method',
            'filter_value'=>'and,or'  
        ),
        'conditional_variable_logic_and' => array(
            'name' => __( 'Column logic', 'super-forms' ), 
            'default'=> ( !isset( $attributes['conditional_variable_logic_and'] ) ? '' : $attributes['conditional_variable_logic_and'] ),
            'type' => 'select',
            'values' => array(
                ''=>'- select -',
                'contains'=>'?? Contains',
                'equal'=>'== Equal',
                'not_equal'=>'!= Not equal',
                'greater_than'=>'> Greater than',
                'less_than'=>'<  Less than',
                'greater_than_or_equal'=>'>= Greater than or equal to',
                'less_than_or_equal'=>'<= Less than or equal',
            ),
            'filter'=>true,
            'parent'=>'conditional_variable_and_method',
            'filter_value'=>'and,or'  
        ),
        'conditional_variable_delimiter' => array(
            'name' => __( 'Custom delimiter', 'super-forms' ), 
            'desc' => __( 'Set a custom delimiter to seperate the values on each row' ), 
            'default'=> ( !isset( $attributes['conditional_variable_delimiter'] ) ? ',' : $attributes['conditional_variable_delimiter'] ),
            'filter'=>true,
            'parent'=>'conditional_variable_method',
            'filter_value'=>'csv'
        ),
        'conditional_variable_enclosure' => array(
            'name' => __( 'Custom enclosure', 'super-forms' ), 
            'desc' => __( 'Set a custom enclosure character for values' ), 
            'default'=> ( !isset( $attributes['conditional_variable_enclosure'] ) ? '"' : $attributes['conditional_variable_enclosure'] ),
            'filter'=>true,
            'parent'=>'conditional_variable_method',
            'filter_value'=>'csv'
        ),

        'conditional_items' => array( 
            'name'=>__( 'Conditions', 'super-forms' ), 
            'desc'=>__( 'The conditions that this element should listen to.', 'super-forms' ),
            'type'=>'variable_conditions',
            'default'=> (!isset($attributes['conditional_items']) ? '' : $attributes['conditional_items']),
            'filter'=>true,
            'parent'=>'conditional_variable_method',
            'filter_value'=>'manual'
        )
    )
);

// @since 1.9
$class = array(
    'name' => __( 'Custom field class', 'super-forms' ),
    'desc' => '(' . __( 'Add a custom class to append extra styles', 'super-forms' ) . ')',
    'default'=> ( !isset( $attributes['class'] ) ? '' : $attributes['class'] ),
    'type'=>'text',
);
$wrapper_class = array(
    'name' => __( 'Custom wrapper class', 'super-forms' ),
    'desc' => '(' . __( 'Add a custom class to append extra styles', 'super-forms' ) . ')',
    'default'=> ( !isset( $attributes['wrapper_class'] ) ? '' : $attributes['wrapper_class'] ),
    'type'=>'text',
);

// @since 3.2.0 - custom TAB index
$custom_tab_index = array(
    'name' => __( 'Custom TAB index', 'super-forms' ),
    'desc' => '(' . __( 'Add a custom TAB index (order) for this field', 'super-forms' ) . ')',
    'type' => 'slider',
    'default'=> (!isset($attributes['custom_tab_index']) ? -1 : $attributes['custom_tab_index']),
    'min' => -1,
    'max' => 50,
    'steps' => 10,
    'desc' => __( 'Set to -1 to use default TAB index.', 'super-forms' )
);