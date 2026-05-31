<?php
$name = array(
    'name'=>esc_html__( 'Name', 'super-forms' ), 
    'desc'=>esc_html__( 'Unique field name (required)', 'super-forms' ),
    'default'=> (!isset($attributes['name']) ? '' : $attributes['name']),
    'required'=>true, 
);
$label = array(
    'name'=>esc_html__( 'Field Label', 'super-forms' ), 
    'label'=>esc_html__( 'Will be visible in front of your field. (leave blank to remove)', 'super-forms' ),
    'default'=> (!isset($attributes['label']) ? '' : $attributes['label']),
    'i18n' => true
);
$description = array(
    'name'=>esc_html__( 'Field description', 'super-forms' ), 
    'label'=>esc_html__( 'Will be visible in front of your field. (leave blank to remove)', 'super-forms' ),
    'default'=> (!isset($attributes['description']) ? '' : $attributes['description']),
    'i18n' => true
);
$tooltip = array(
    'default'=> (!isset($attributes['tooltip']) ? '' : $attributes['tooltip']),
    'name'=>esc_html__( 'Tooltip text', 'super-forms' ), 
    'desc'=>esc_html__( 'The tooltip will appear as soon as the user hovers over the field with their mouse.', 'super-forms' ),
    'i18n' => true
);        
$extensions = array(
    'default'=> (!isset($attributes['extensions']) ? 'jpg|jpeg|png|gif|pdf' : $attributes['extensions']),
    'type' => 'textarea', 
    'name' => esc_html__( 'Allowed Extensions (seperated by pipes)', 'super-forms' ),
    'label' => esc_html__( 'Example', 'super-forms' ).': jpg|jpeg|png|gif|pdf'
);
$special_validations = array(
    'name'=>esc_html__( 'Validation', 'super-forms' ), 
    'label'=>esc_html__( 'How does this field need to be validated?', 'super-forms' ), 
    'default'=> (!isset($attributes['validation']) ? 'none' : $attributes['validation']),
    'type'=>'select',
    'values'=>array(
        'none' => esc_html__( 'None', 'super-forms' ),
        'empty' => esc_html__( 'Required Field (not empty)', 'super-forms' ), 
        'email' => esc_html__( 'E-mail address', 'super-forms' ), 
        'phone' => esc_html__( 'Phone number', 'super-forms' ), 
        'numeric' => esc_html__( 'Numeric', 'super-forms' ),
        'float' => esc_html__( 'Float', 'super-forms' ),
        'website' => esc_html__( 'Website URL', 'super-forms' ),
        'iban' => esc_html__( 'IBAN', 'super-forms' ),
        'custom' => esc_html__( 'Custom Regex', 'super-forms' ),
    ),
    'filter'=>true
);
$custom_regex = array(
    'default'=> (!isset($attributes['custom_regex']) ? '' : $attributes['custom_regex']),
    'name'=>esc_html__( 'Custom Regex', 'super-forms' ), 
    'label'=>sprintf( esc_html__( 'Click here to find %1$sexample regular expressions%2$s, or create your own easily on %3$sregex101.com%4$s', 'super-forms' ), '<a target="_blank" href="https://regex101.com/library?orderBy=MOST_POINTS">', '</a>', '<a target="_blank" href="https://regex101.com/">', '</a>'), 
    'desc'=>esc_html__( 'Use your own custom regex to validate this field', 'super-forms' ),
    'filter'=>true,
    'parent'=>'validation',
    'filter_value'=>'custom'
);
$validation_empty = array(
    'name'=>esc_html__( 'Validation', 'super-forms' ), 
    'label'=>esc_html__( 'How does this field need to be validated?', 'super-forms' ), 
    'default'=> (!isset($attributes['validation']) ? 'none' : $attributes['validation']),
    'type'=>'select', 
    'values'=>array(
        'none' => esc_html__( 'None', 'super-forms' ), 
        'empty' => esc_html__( 'Required Field (not empty)', 'super-forms' )
    ),
    'filter'=>true
);
$validation_empty_plus_regex = array(
    'name'=>esc_html__( 'Validation', 'super-forms' ), 
    'label'=>esc_html__( 'How does this field need to be validated?', 'super-forms' ), 
    'default'=> (!isset($attributes['validation']) ? 'none' : $attributes['validation']),
    'type'=>'select', 
    'values'=>array(
        'none' => esc_html__( 'None', 'super-forms' ), 
        'empty' => esc_html__( 'Required Field (not empty)', 'super-forms' ),
        'custom' => esc_html__( 'Custom Regex', 'super-forms' ),
    ),
    'filter'=>true
);

$validation_not_empty = array(
    'name'=>esc_html__( 'Validation', 'super-forms' ), 
    'label'=>esc_html__( 'How does this field need to be validated?', 'super-forms' ), 
    'default'=> (!isset($attributes['validation']) ? 'empty' : $attributes['validation']),
    'type'=>'select', 
    'values'=>array(
        'none' => esc_html__( 'None', 'super-forms' ), 
        'empty' => esc_html__( 'Required Field (not empty)', 'super-forms' )
    ),
    'filter'=>true
);
$error = array(
    'default'=> (!isset($attributes['error']) ? '' : $attributes['error']),
    'name'=>esc_html__( 'Validation error message', 'super-forms' ), 
    'label'=>esc_html__( 'Error message to display to the user when the field was filled out incorrectly (when the validation failed)', 'super-forms' ),
    'i18n' => true
);
$emptyError = array(
    'default'=> (!isset($attributes['emptyError']) ? '' : $attributes['emptyError']),
    'name'=>esc_html__( '(optional) Empty error message', 'super-forms' ), 
    'label'=>esc_html__( 'Error message to display to the user when the field was not filled out. Falls back to the "Validation error message" if left blank.', 'super-forms' ),
    'i18n' => true
);
$allow_empty = array(
    'name'=>esc_html__( 'Allow field to be empty', 'super-forms' ), 
    'label'=>esc_html__( 'Only apply the validation if field is not empty', 'super-forms' ), 
    'default'=> (!isset($attributes['may_be_empty']) ? 'false' : $attributes['may_be_empty']),
    'type'=>'select', 
    'values'=>array(
        'false' => esc_html__( 'No', 'super-forms' ), 
        'true' => esc_html__( 'Yes', 'super-forms' ),
        'conditions' => esc_html__( 'Yes, but not when the following conditions are met', 'super-forms' )
    ),
    'filter'=>true,
    'parent'=>'validation',
    'filter_value'=>'empty,email,phone,numeric,float,website,iban,custom'
);
$allow_empty_no_filter = array(
    'name'=>esc_html__( 'Allow field to be empty', 'super-forms' ), 
    'label'=>esc_html__( 'Only apply the validation if field is not empty', 'super-forms' ), 
    'default'=> (!isset($attributes['may_be_empty']) ? 'false' : $attributes['may_be_empty']),
    'type'=>'select', 
    'values'=>array(
        'false' => esc_html__( 'No', 'super-forms' ), 
        'true' => esc_html__( 'Yes', 'super-forms' ),
        'conditions' => esc_html__( 'Yes, but not when the following conditions are met', 'super-forms' )
    ),
    'filter'=>true,
);
$allow_empty_conditions = array(
    'name'=>esc_html__( 'Conditions', 'super-forms' ), 
    'label'=>esc_html__( 'Validate the field when the following conditions are met.', 'super-forms' ),
    'type'=>'conditions',
    'default'=> (!isset($attributes['may_be_empty_conditions']) ? '' : $attributes['may_be_empty_conditions']),
    'filter'=>true,
    'parent'=>'may_be_empty',
    'filter_value'=>'conditions' 
);

// @since   1.0.6
$conditional_validation = array(
    'name'=>esc_html__( 'Conditional Validation', 'super-forms' ), 
    'label'=>esc_html__( 'Add some extra validation for this field', 'super-forms' ), 
    'default'=> (!isset($attributes['conditional_validation']) ? 'none' : $attributes['conditional_validation']),
    'type'=>'select', 
    'filter'=>true,
    'values'=>array(
        'none' => esc_html__( 'None', 'super-forms' ),
        'contains' => esc_html__( '?? Contains', 'super-forms' ),
        'not_contains' => esc_html__( '!! Not contains', 'super-forms' ),
        'equal' => esc_html__( '== Equal', 'super-forms' ),
        'not_equal' => esc_html__( '!= Not equal', 'super-forms' ),
        'greater_than' => esc_html__( '&gt; Greater than', 'super-forms' ),
        'less_than' => esc_html__( '&lt;  Less than', 'super-forms' ),
        'greater_than_or_equal' => esc_html__( '&gt;= Greater than or equal to', 'super-forms' ),
        'less_than_or_equal' => esc_html__( '&lt;= Less than or equal', 'super-forms' ),

        // @since 3.6.0 - more specific conditional validation options
        // > && <
        // > || <
        'greater_than_and_less_than' => esc_html__( '&gt; && &lt; Greater than AND Less than', 'super-forms' ),
        'greater_than_or_less_than' => esc_html__( '&gt; || &lt; Greater than OR Less than', 'super-forms' ),
        // >= && <
        // >= || <
        'greater_than_or_equal_and_less_than' => esc_html__( '&gt;= && &lt; Greater than or equal to AND Less than', 'super-forms' ),
        'greater_than_or_equal_or_less_than' => esc_html__( '&gt;= || &lt; Greater than or equal to OR Less than', 'super-forms' ),
        // > && <=
        // > || <=
        'greater_than_and_less_than_or_equal' => esc_html__( '&gt; && &lt;= Greater than AND Less than or equal to', 'super-forms' ),
        'greater_than_or_less_than_or_equal' => esc_html__( '&gt; || &lt;= Greater than OR Less than or equal to', 'super-forms' ),
        // >= && <=
        // >= || <=
        'greater_than_or_equal_and_less_than_or_equal' => esc_html__( '&gt;= && &lt;= Greater than or equal to AND Less than or equal to', 'super-forms' ),
        'greater_than_or_equal_or_less_than_or_equal' => esc_html__( '&gt;= || &lt;= Greater than or equal to OR Less than or equal to', 'super-forms' ),
    )
);
$conditional_validation_value = array(
    'name'=>esc_html__( 'Conditional Validation Value', 'super-forms' ), 
    'label'=>esc_html__( 'Enter the value you want to validate', 'super-forms' ), 
    'default'=> (!isset($attributes['conditional_validation_value']) ? '' : $attributes['conditional_validation_value']),
    'filter'=>true,
    'parent'=>'conditional_validation',
    'filter_value'=>'contains,not_contains,equal,not_equal,greater_than,less_than,greater_than_or_equal,less_than_or_equal,greater_than_and_less_than,greater_than_or_less_than,greater_than_or_equal_and_less_than,greater_than_or_equal_or_less_than,greater_than_and_less_than_or_equal,greater_than_or_less_than_or_equal,greater_than_or_equal_and_less_than_or_equal,greater_than_or_equal_or_less_than_or_equal'
);
$conditional_validation_value2 = array(
    'name'=>esc_html__( 'Conditional Validation Value 2', 'super-forms' ), 
    'label'=>esc_html__( 'Enter the second value you want to validate', 'super-forms' ), 
    'default'=> (!isset($attributes['conditional_validation_value2']) ? '' : $attributes['conditional_validation_value2']),
    'filter'=>true,
    'parent'=>'conditional_validation',
    'filter_value'=>'greater_than_and_less_than,greater_than_or_less_than,greater_than_or_equal_and_less_than,greater_than_or_equal_or_less_than,greater_than_and_less_than_or_equal,greater_than_or_less_than_or_equal,greater_than_or_equal_and_less_than_or_equal,greater_than_or_equal_or_less_than_or_equal'
);  
$grouped = array(
    'name' => esc_html__( 'Individual / Grouped', 'super-forms' ), 
    'label' => esc_html__( 'Select grouped, if you wish to append the field next to it\'s previous field.', 'super-forms' ), 
    'default'=> (!isset($attributes['grouped']) ? 0 : $attributes['grouped']),
    'type' => 'select', 
    'values' => array(
        '0' => esc_html__( 'Individual field', 'super-forms' ), 
        '1' => esc_html__( 'Grouped field', 'super-forms' ),
        '2' => esc_html__( 'Last Grouped field (closes/ends a group)', 'super-forms' )
    )
);
$disabled = array(
    'name' => esc_html__( 'Disable the input field', 'super-forms' ), 
    'label' => esc_html__( 'Make this field disabled, this way a user cannot edit the field value', 'super-forms' ), 
    'default'=> (!isset($attributes['disabled']) ? '' : $attributes['disabled']),
    'type' => 'select', 
    'values' => array(
        '' => esc_html__( 'No (users can edit the value)', 'super-forms' ), 
        '1' => esc_html__( 'Yes (users can\'t edit the value)', 'super-forms' ), 
    )
);
$readonly = array(
    'name' => esc_html__( 'Make field read-only', 'super-forms' ), 
    'label' => esc_html__( 'A read-only input field cannot be modified (however, a user can tab to it, highlight it, and copy the text from it).', 'super-forms' ), 
    'default'=> (!isset($attributes['readonly']) ? '' : $attributes['readonly']),
    'type' => 'checkbox', 
    'values' => array(
        'true' => esc_html__( 'Enable read-only', 'super-forms' ), 
    )
);
$autocomplete = array(
    'desc' => esc_html__( 'This will prevent browser from automatically autopopulating a field when user starts typing with previously submitted data', 'super-forms' ), 
    'default'=> (!isset($attributes['autocomplete']) ? '' : $attributes['autocomplete']),
    'type' => 'checkbox', 
    'values' => array(
        'true' => esc_html__( 'Disable autocompletion', 'super-forms' ), 
    )
);
$maxlength = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['maxlength']) ? 0 : $attributes['maxlength']),
    'min' => 0, 
    'max' => 100, 
    'steps' => 1, 
    'name' => esc_html__( 'Max characters/selections allowed', 'super-forms' ), 
    'desc' => esc_html__( 'Set to 0 to remove limitations.', 'super-forms' )
);
$minlength = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['minlength']) ? 0 : $attributes['minlength']),
    'min' => 0, 
    'max' => 100, 
    'steps' => 1, 
    'name' => esc_html__( 'Min characters/selections allowed', 'super-forms' ), 
    'desc' => esc_html__( 'Set to 0 to remove limitations.', 'super-forms' )
);
$maxnumber = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['maxnumber']) ? 0 : $attributes['maxnumber']),
    'min' => 0, 
    'max' => 100, 
    'steps' => 1, 
    'name' => esc_html__( 'Max number allowed', 'super-forms' ), 
    'desc' => esc_html__( 'Set to 0 to remove limitations.', 'super-forms' )
);
$minnumber = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['minnumber']) ? 0 : $attributes['minnumber']),
    'min' => 0, 
    'max' => 100, 
    'steps' => 1, 
    'name' => esc_html__( 'Min number allowed', 'super-forms' ), 
    'desc' => esc_html__( 'Set to 0 to remove limitations.', 'super-forms' )
);
$width = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['width']) ? 0 : $attributes['width']),
    'min' => 0, 
    'max' => 600, 
    'steps' => 10, 
    'name' => esc_html__( 'Field width in pixels', 'super-forms' ), 
    'desc' => esc_html__( 'Set to 0 to use default CSS width.', 'super-forms' )
);
$wrapper_width = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['wrapper_width']) ? 0 : $attributes['wrapper_width']),
    'min' => 0, 
    'max' => 600, 
    'steps' => 10, 
    'name' => esc_html__( 'Wrapper width in pixels', 'super-forms' ), 
    'desc' => esc_html__( 'Set to 0 to use default CSS width.', 'super-forms' )
);
$height = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['height']) ? 0 : $attributes['height']),
    'min' => 0, 
    'max' => 600, 
    'steps' => 10, 
    'name' => esc_html__( 'Field height in pixels', 'super-forms' ), 
    'desc' => esc_html__( 'Set to 0 to use default CSS height.', 'super-forms' )
);
$exclude = array(
    'name'=>esc_html__( 'Exclude from email', 'super-forms' ), 
    'desc'=>esc_html__( 'You can prevent this data from being send to the form submitter (if the option to send confirmation email has been enabled).', 'super-forms' ), 
    'default'=> (!isset($attributes['exclude']) ? 0 : $attributes['exclude']),
    'type'=>'select', 
    'values'=>array(
        '0'=>esc_html__( 'Do not exclude from emails', 'super-forms' ),
        '1'=>esc_html__( 'Exclude from confirmation email', 'super-forms' ), 
        // @since 4.9.471
        '3'=>esc_html__( 'Exclude from admin email', 'super-forms' ),
        // @since 1.0.4
        '2'=>esc_html__( 'Exclude from all emails', 'super-forms' )
    )
);
// The following is required for backward compatibility with older HTML (raw) elements
// When a user edits an old HTML element the setting to exclude it from entry should be activated by default
$exclude_for_html_element = $exclude;
$exclude_for_html_element['allow_empty'] = true;

// @since 3.9.0 - replace comma's with specific html tag in emails for radio/checkbox/dropdown elements
$replace_commas = array(
    'name'=>esc_html__( 'Replace comma\'s with HTML tag in emails', 'super-forms' ), 
    'label'=>esc_html__( 'With this setting you can use &lt;br /&gt; to use line breaks for each selected option instead of a comma seperated list. Of course you can also use other HTML if necessary.', 'super-forms' ), 
    'desc'=>esc_html__( 'Leave empty for no replacement', 'super-forms' ), 
    'default'=> (!isset($attributes['replace_commas']) ? '' : $attributes['replace_commas']),
);

// @since 3.3.0
$exclude_entry = array(
    'desc'=>esc_html__( 'Wether or not to prevent this field from being saved in Contact Entry.', 'super-forms' ), 
    'default'=> ( !isset( $attributes['exclude_entry'] ) ? '' : $attributes['exclude_entry'] ),
    'type' => 'checkbox', 
    'values' => array(
        'true' => esc_html__( 'Do not save field in Contact Entry', 'super-forms' ),
    )
);
// The following is required for backward compatibility with older HTML (raw) elements
// When a user edits an old HTML element the setting to exclude it from entry should be activated by default
$exclude_entry_for_html_element = $exclude_entry;
$exclude_entry_for_html_element['allow_empty'] = true;

// @since 1.2.7
$admin_email_value = array(
    'name'=>esc_html__( 'Send value or label to admin?', 'super-forms' ), 
    'desc'=>esc_html__( 'Choose to send only the selected option label or value to the admin', 'super-forms' ), 
    'default'=> (!isset($attributes['admin_email_value']) ? 'value' : $attributes['admin_email_value']),
    'type'=>'select', 
    'values'=>array(
        'value'=>esc_html__( 'Only send the value', 'super-forms' ),
        'label'=>esc_html__( 'Only send the label', 'super-forms' ),
        'both'=>esc_html__( 'Send both value and label', 'super-forms' ),
    )
);
$confirm_email_value = array(
    'name'=>esc_html__( 'Send value or label to submitter?', 'super-forms' ), 
    'desc'=>esc_html__( 'Choose to send only the selected option label or value to the submitter', 'super-forms' ), 
    'default'=> (!isset($attributes['confirm_email_value']) ? 'value' : $attributes['confirm_email_value']),
    'type'=>'select', 
    'values'=>array(
        'value'=>esc_html__( 'Only send the value', 'super-forms' ),
        'label'=>esc_html__( 'Only send the label', 'super-forms' ),
        'both'=>esc_html__( 'Send both value and label', 'super-forms' ),
    )
);

// @since 1.2.9
$contact_entry_value = array(
    'name'=>esc_html__( 'Save value or label to entry?', 'super-forms' ), 
    'label'=>esc_html__( 'It is recommended to always only save it\'s value, unless you really require otherwise', 'super-forms' ), 
    'desc'=>esc_html__( 'Choose to save only the selected option label or value as contact entry', 'super-forms' ), 
    'default'=> (!isset($attributes['contact_entry_value']) ? 'value' : $attributes['contact_entry_value']),
    'type'=>'select', 
    'values'=>array(
        'value'=>esc_html__( 'Only save the value (recommended)', 'super-forms' ),
        'label'=>esc_html__( 'Only save the label', 'super-forms' ),
        'both'=>esc_html__( 'Save both value and label', 'super-forms' ),
    )
);

$error_position = array(
    'name'=>esc_html__( 'Error message positioning', 'super-forms' ), 
    'default'=> (!isset($attributes['error_position']) ? '' : $attributes['error_position']),
    'type'=>'select', 
    'values'=>array(
        ''=>esc_html__( 'Default positioning (bottom right)', 'super-forms' ), 
        'bottom-right'=>esc_html__( 'Bottom right', 'super-forms' ), 
        'bottom-left'=>esc_html__( 'Bottom left', 'super-forms' ), 
        'top-right'=>esc_html__( 'Top right', 'super-forms' ), 
        'top-left'=>esc_html__( 'Top left', 'super-forms' ), 
    )
); 
$error_position_left_only = array(
    'name'=>esc_html__( 'Error message positioning', 'super-forms' ), 
    'default'=> (!isset($attributes['error_position']) ? '' : $attributes['error_position']),
    'type'=>'select', 
    'values'=>array(
        ''=>esc_html__( 'Default positioning', 'super-forms' ), 
        'bottom-left'=>esc_html__( 'Bottom left', 'super-forms' ), 
        'top-left'=>esc_html__( 'Top left', 'super-forms' ), 
    )
);         
$styles = array(
    'type' => 'textarea', 
    'default'=> (!isset($attributes['styles']) ? '' : $attributes['styles']),
    'name' => esc_html__( 'Extra styles', 'super-forms' ), 
    'desc' => esc_html__( 'Use this to add some extra styles for this element.', 'super-forms' ),
);
$conditional_field_name = array(
    'name'=>esc_html__( 'Retrieve value from', 'super-forms' ), 
    'desc'=>esc_html__( 'Based on the above selected field value this element will be vissible or hidden.', 'super-forms' ),
    'type' => 'previously_created_fields',
    'default'=> (!isset($attributes['conditional_field']) ? '' : $attributes['conditional_field']),
    'values' => array(
        '' => '- select a field -',
    ),
);        
$conditional_logic = array(
    'default'=> (!isset($attributes['logic']) ? '' : $attributes['logic']),
    'name'=>esc_html__( 'Conditional logic', 'super-forms' ), 
    'desc'=>esc_html__( 'The logic/method of the validation.', 'super-forms' ),
    'type'=>'select',
    'values'=> array(
        'contains'=>'?? '.esc_html__( 'Contains', 'super-forms' ),
        'not_contains'=>'!! '.esc_html__( 'Not contains', 'super-forms' ),
        'equal'=>'== '.esc_html__( 'Equal', 'super-forms' ),
        'not_equal'=>'!= '.esc_html__( 'Not equal', 'super-forms' ),
        'greater_than'=>'> '.esc_html__( 'Greater than', 'super-forms' ),
        'less_than'=>'<  '.esc_html__( 'Less than', 'super-forms' ),
        'greater_than_or_equal'=>'>= '.esc_html__( 'Greater than or equal to', 'super-forms' ),
        'less_than_or_equal'=>'<= '.esc_html__( 'Less than or equal to', 'super-forms' ),
    ),
);
$conditional_field_value = array(
    'default'=> (!isset($attributes['value']) ? '' : $attributes['value']),
    'name'=>esc_html__( 'Conditional field value', 'super-forms' ), 
    'desc'=>esc_html__( 'The value the field needs to have before this field will become visible.', 'super-forms' )
);
$animation = array(
    '' => esc_html__( 'No animation', 'super-forms' ),
    'fade-in' => esc_html__( 'Fade in without movement', 'super-forms' ), 
    'fade-in-up' => esc_html__( 'Fade in down to up', 'super-forms' ), 
    'fade-in-left' => esc_html__( 'Fade in right to left', 'super-forms' ),
    'fade-in-right' => esc_html__( 'Fade in left to right', 'super-forms' ),
    'fade-in-down' => esc_html__( 'Fade in up to down', 'super-forms' )
);
$icon_position = array(
    'default'=> (!isset($attributes['icon_position']) ? 'outside' : $attributes['icon_position']),
    'name'=>esc_html__( 'Icon positioning', 'super-forms' ), 
    'desc'=>esc_html__( 'How to display your icon.', 'super-forms' ),
    'type'=>'select',
    'values'=> array(
        'inside'=>esc_html__( 'Inside the field', 'super-forms' ),
        'outside'=>esc_html__( 'Outside the field', 'super-forms' ),
    ),
);
$icon_align = array(
    'default'=> (!isset($attributes['icon_align']) ? 'left' : $attributes['icon_align']),
    'name'=>esc_html__( 'Icon alignment', 'super-forms' ), 
    'desc'=>esc_html__( 'Align icon to the left or right.', 'super-forms' ),
    'type'=>'select',
    'values'=> array(
        'left'=>esc_html__( 'Left', 'super-forms' ),
        'right'=>esc_html__( 'Right', 'super-forms' ),
    ),
);
$icon = array(
    'default'=> (!isset($attributes['icon']) ? 'user' : $attributes['icon']),
    'name'=>esc_html__( 'Select an Icon', 'super-forms' ), 
    'type'=>'icon',
    'desc'=>esc_html__( 'Leave blank if you prefer to not use an icon.', 'super-forms' )
);

$conditional_action = array(
    'name'=>esc_html__( 'Action', 'super-forms' ), 
    'desc'=>esc_html__( 'Based on your conditions you can choose to hide, show elements or make fields readonly.', 'super-forms' ), 
    'default'=> (!isset($attributes['conditional_action']) ? 'disabled' : $attributes['conditional_action']),
    'type'=>'select',
    'values'=>array(
        'disabled'=>esc_html__( 'Disabled (do not use conditional logic)', 'super-forms' ),
        'show'=>esc_html__( 'Show', 'super-forms' ),
        'hide'=>esc_html__( 'Hide', 'super-forms' ),
        'readonly'=>esc_html__( 'Readonly (makes fields readonly)', 'super-forms' ),
    ),
    'filter'=>true,
);
$conditional_trigger = array(
    'name'=>esc_html__( 'When to Trigger?', 'super-forms' ), 
    'desc'=>esc_html__( 'Trigger only when all or one of the below conditions matched their value.', 'super-forms' ), 
    'default'=> (!isset($attributes['conditional_trigger']) ? 'all' : $attributes['conditional_trigger']),
    'type'=>'select',
    'values'=>array(
        'all'=>esc_html__( 'All (when all conditions matched)', 'super-forms' ),
        'one'=>esc_html__( 'One (when one condition matched)', 'super-forms' ),
    ),
    'filter'=>true,
    'parent'=>'conditional_action',
    'filter_value'=>'show,hide,readonly'
);
$conditional_logic_array = array(
    'name' => esc_html__( 'Conditional Logic', 'super-forms' ),
    'fields' => array(
        'conditional_action' => $conditional_action,
        'conditional_trigger' => $conditional_trigger,
        'conditional_items' => array( 
            'name'=>esc_html__( 'Conditions', 'super-forms' ), 
            'desc'=>esc_html__( 'The conditions that this element should listen to.', 'super-forms' ),
            'type'=>'conditions',
            'default'=> (!isset($attributes['conditional_items']) ? '' : $attributes['conditional_items']),
            'filter'=>true,
            'parent'=>'conditional_action',
            'filter_value'=>'show,hide,readonly' 
        )
    )   
);

// @since 1.2.7
$conditional_variable_array = array(
    'name' => esc_html__( 'Conditional Variable (dynamic value)', 'super-forms' ),
    'fields' => array(
        'conditional_variable_action' => array(
            'name'=>esc_html__( 'Make field variable', 'super-forms' ), 
            'desc'=>esc_html__( 'Choose to make this field a variable or not.', 'super-forms' ), 
            'default'=> (!isset($attributes['conditional_variable_action']) ? 'disabled' : $attributes['conditional_variable_action']),
            'type'=>'select',
            'values'=>array(
                'disabled'=>esc_html__( 'Disabled (do not make variable)', 'super-forms' ),
                'enabled'=>esc_html__( 'Enabled (make variable)', 'super-forms' ),
            ),
            'filter'=>true,
        ),
        
        // @since 4.2.0 - allow to retrieve conditions via CSV files
        'conditional_variable_method' => array(
            'name'=>esc_html__( 'Retrieve method', 'super-forms' ),
            'desc'=>esc_html__( 'Select how you would want to define the conditions (manually or via a CSV file)', 'super-forms' ), 
            'default'=> (!isset($attributes['conditional_variable_method']) ? 'manual' : $attributes['conditional_variable_method']),
            'type'=>'select',
            'values'=>array(
                'manual'=>esc_html__( 'Manually enter each condition (default)', 'super-forms' ),
                'csv'=>esc_html__( 'CSV file', 'super-forms' ),
            ),
            'filter'=>true,
            'parent'=>'conditional_variable_action',
            'filter_value'=>'enabled'
        ),
        'conditional_variable_csv' => array(
            'name' => esc_html__( 'Upload CSV file', 'super-forms' ), 
            'label' => sprintf( esc_html__( 'Please read the %sCreating variable conditions with CSV file%s section in the documentation before using this method.', 'super-forms' ), '<a target="_blank" href="https://renstillmann.github.io/super-forms/#/variable-fields?id=creating-variable-conditions-with-csv-file">', '</a>' ),
            'default'=> ( !isset( $attributes['conditional_variable_csv'] ) ? '' : $attributes['conditional_variable_csv'] ),
            'type' => 'file',
            'filter'=>true,
            'parent'=>'conditional_variable_method',
            'filter_value'=>'csv',
            'file_type'=>'text/csv'
        ),
        'conditional_variable_row' => array(
            'name' => esc_html__( 'Row heading', 'super-forms' ), 
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
            'name' => esc_html__( 'Row logic', 'super-forms' ), 
            'default'=> ( !isset( $attributes['conditional_variable_logic'] ) ? '' : $attributes['conditional_variable_logic'] ),
            'type' => 'select',
            'values' => array(
                ''=>'- select -',
                'contains' => '?? Contains',
                'not_contains' => '!! Not contains',
                'equal' => '== Equal',
                'not_equal' => '!= Not equal',
                'greater_than' => '> Greater than',
                'less_than' => '<  Less than',
                'greater_than_or_equal' => '>= Greater than or equal to',
                'less_than_or_equal' => '<= Less than or equal',
            ),
            'filter'=>true,
            'parent'=>'conditional_variable_method',
            'filter_value'=>'csv'  
        ),
        'conditional_variable_and_method' => array(
            'name' => esc_html__( 'Compare method (OR / AND)', 'super-forms' ), 
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
            'name' => esc_html__( 'Column heading', 'super-forms' ), 
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
            'name' => esc_html__( 'Column logic', 'super-forms' ), 
            'default'=> ( !isset( $attributes['conditional_variable_logic_and'] ) ? '' : $attributes['conditional_variable_logic_and'] ),
            'type' => 'select',
            'values' => array(
                '' => '- select -',
                'contains' => '?? Contains',
                'not_contains' => '!! Not contains',
                'equal' => '== Equal',
                'not_equal' => '!= Not equal',
                'greater_than' => '> Greater than',
                'less_than' => '<  Less than',
                'greater_than_or_equal' => '>= Greater than or equal to',
                'less_than_or_equal' => '<= Less than or equal',
            ),
            'filter'=>true,
            'parent'=>'conditional_variable_and_method',
            'filter_value'=>'and,or'  
        ),
        'conditional_variable_delimiter' => array(
            'name' => esc_html__( 'Custom delimiter', 'super-forms' ), 
            'desc' => esc_html__( 'Set a custom delimiter to seperate the values on each row', 'super-forms' ), 
            'default'=> ( !isset( $attributes['conditional_variable_delimiter'] ) ? ',' : $attributes['conditional_variable_delimiter'] ),
            'filter'=>true,
            'parent'=>'conditional_variable_method',
            'filter_value'=>'csv'
        ),
        'conditional_variable_enclosure' => array(
            'name' => esc_html__( 'Custom enclosure', 'super-forms' ), 
            'desc' => esc_html__( 'Set a custom enclosure character for values', 'super-forms' ), 
            'default'=> ( !isset( $attributes['conditional_variable_enclosure'] ) ? '"' : $attributes['conditional_variable_enclosure'] ),
            'filter'=>true,
            'parent'=>'conditional_variable_method',
            'filter_value'=>'csv'
        ),

        'conditional_variable_items' => array( 
            'name'=>esc_html__( 'Conditions', 'super-forms' ), 
            'desc'=>esc_html__( 'The conditions that this element should listen to.', 'super-forms' ),
            'type'=>'variable_conditions',
            // Backwards compatibility to make sure old variable fields will keep working correctly.
            'default'=> (isset($attributes['conditional_variable_items']) ? $attributes['conditional_variable_items'] : (!isset($attributes['conditional_items']) ? '' : $attributes['conditional_items']) ),
            'filter'=>true,
            'parent'=>'conditional_variable_method',
            'filter_value'=>'manual'
        )
    )
);

// @since 1.9
$class = array(
    'name' => esc_html__( 'Custom field class', 'super-forms' ),
    'desc' => '(' . esc_html__( 'Add a custom class to append extra styles', 'super-forms' ) . ')',
    'default'=> ( !isset( $attributes['class'] ) ? '' : $attributes['class'] ),
    'type'=>'text',
);
$wrapper_class = array(
    'name' => esc_html__( 'Custom wrapper class', 'super-forms' ),
    'desc' => '(' . esc_html__( 'Add a custom class to append extra styles', 'super-forms' ) . ')',
    'default'=> ( !isset( $attributes['wrapper_class'] ) ? '' : $attributes['wrapper_class'] ),
    'type'=>'text',
);

// @since 3.2.0 - custom TAB index
$custom_tab_index = array(
    'name' => esc_html__( 'Custom TAB index', 'super-forms' ),
    'desc' => '(' . esc_html__( 'Add a custom TAB index (order) for this field', 'super-forms' ) . ')',
    'type' => 'slider',
    'default'=> (!isset($attributes['custom_tab_index']) ? -1 : $attributes['custom_tab_index']),
    'min' => -1,
    'max' => 50,
    'steps' => 10,
    'desc' => esc_html__( 'Set to -1 to use default TAB index.', 'super-forms' )
);
