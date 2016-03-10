<?php
$name = array(
    'name'=>__( 'Name', 'super' ), 
    'desc'=>__( 'Unique field name (required)', 'super' ),
    'default'=> (!isset($attributes['name']) ? '' : $attributes['name']),
    'required'=>true, 
);
$email = array(
    'name'=>__( 'Email Label', 'super' ), 
    'desc'=>__( 'Indicates the field in the email template. (required)', 'super' ),
    'default'=> (!isset($attributes['email']) ? '' : $attributes['email']),
    'required'=>true, 
);
$label = array(
    'name'=>__( 'Field Label', 'super' ), 
    'desc'=>__( 'Will be visible in front of your field. (leave blank to remove)', 'super' ),
    'default'=> (!isset($attributes['label']) ? '' : $attributes['label']),
);
$description = array(
    'name'=>__( 'Field description', 'super' ), 
    'desc'=>__( 'Will be visible in front of your field. (leave blank to remove)', 'super' ),
    'default'=> (!isset($attributes['description']) ? '' : $attributes['description']),
);
$tooltip = array(
    'default'=> (!isset($attributes['tooltip']) ? '' : $attributes['tooltip']),
    'name'=>__( 'Tooltip text', 'super' ), 
    'desc'=>__( 'The tooltip will appear as soon as the user hovers over the field with their mouse.', 'super' )
);        
$extensions = array(
    'default'=> (!isset($attributes['extensions']) ? '' : $attributes['extensions']),
    'type' => 'textarea', 
    'name' => __( 'Allowed Extensions (seperated by pipes)', 'super' ),
    'desc' => __( 'Example', 'super' ).': jpg|jpeg|png|gif|pdf'
);
$special_validations = array(
    'name'=>__( 'Special Validation', 'super' ), 
    'desc'=>__( 'How does this field need to be validated?', 'super' ), 
    'default'=> (!isset($attributes['validation']) ? 'none' : $attributes['validation']),
    'type'=>'select', 
    'values'=>array(
        'none' => __( 'No validation needed', 'super' ),
        'empty' => __( 'Not empty', 'super' ), 
        'email' => __( 'Email address', 'super' ), 
        'phone' => __( 'Phone number', 'super' ), 
        'numeric' => __( 'Numeric', 'super' ),
        'website' => __( 'Website URL', 'super' ),
    )
);

// @since   1.0.6
$conditional_validation = array(
    'name'=>__( 'Conditional Validation', 'super' ), 
    'desc'=>__( 'Add some extra validation for this field', 'super' ), 
    'default'=> (!isset($attributes['conditional_validation']) ? 'none' : $attributes['conditional_validation']),
    'type'=>'select', 
    'filter'=>true,
    'values'=>array(
        'none' => __( 'No validation needed', 'super' ),
        'contains' => __( '?? Contains', 'super' ),
        'equal' => __( '== Equal', 'super' ),
        'not_equal' => __( '!= Not equal', 'super' ),
        'greater_than' => __( '&gt; Greater than', 'super' ),
        'less_than' => __( '&lt;  Less than', 'super' ),
        'greater_than_or_equal' => __( '&gt;= Greater than or equal to', 'super' ),
        'less_than_or_equal' => __( '&lt;= Less than or equal', 'super' ),
    )
);
$conditional_validation_value = array(
    'name'=>__( 'Conditional Validation Value', 'super' ), 
    'desc'=>__( 'Enter the value you want to validate', 'super' ), 
    'default'=> (!isset($attributes['conditional_validation_value']) ? '' : $attributes['conditional_validation_value']),
    'filter'=>true,
    'parent'=>'conditional_validation',
    'filter_value'=>'contains,equal,not_equal,greater_than,less_than,greater_than_or_equal,less_than_or_equal'
);

$validation_empty = array(
    'name'=>__( 'Validation', 'super' ), 
    'desc'=>__( 'How does this field need to be validated?', 'super' ), 
    'default'=> (!isset($attributes['validation']) ? 'none' : $attributes['validation']),
    'type'=>'select', 
    'values'=>array(
        'none' => __( 'No validation needed', 'super' ), 
        'empty' => __( 'Not empty', 'super' )
    )
);
$error = array(
    'default'=> (!isset($attributes['error']) ? '' : $attributes['error']),
    'name'=>__( 'Error Message', 'super' ), 
    'desc'=>__( 'A message to show up when field was filled out incorrectly.', 'super' )
);  
$grouped = array(
    'name' => __( 'Individual / Grouped', 'super' ), 
    'desc' => __( 'Select grouped, if you wish to append the field next to it\'s previous field.', 'super' ), 
    'default'=> (!isset($attributes['grouped']) ? 0 : $attributes['grouped']),
    'type' => 'select', 
    'values' => array(
        '0' => __( 'Individual field', 'super' ), 
        '1' => __( 'Grouped field', 'super' ),
        '2' => __( 'Last Grouped field (closes/ends a group)', 'super' )
    )
); 
$maxlength = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['maxlength']) ? 0 : $attributes['maxlength']),
    'min' => 0, 
    'max' => 100, 
    'steps' => 1, 
    'name' => __( 'Max characters/selections allowed', 'super' ), 
    'desc' => __( 'Set to 0 to remove limitations.', 'super' )
);
$minlength = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['minlength']) ? 0 : $attributes['minlength']),
    'min' => 0, 
    'max' => 100, 
    'steps' => 1, 
    'name' => __( 'Min characters/selections allowed', 'super' ), 
    'desc' => __( 'Set to 0 to remove limitations.', 'super' )
);
$maxnumber = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['maxnumber']) ? 0 : $attributes['maxnumber']),
    'min' => 0, 
    'max' => 100, 
    'steps' => 1, 
    'name' => __( 'Max number allowed', 'super' ), 
    'desc' => __( 'Set to 0 to remove limitations.', 'super' )
);
$minnumber = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['minnumber']) ? 0 : $attributes['minnumber']),
    'min' => 0, 
    'max' => 100, 
    'steps' => 1, 
    'name' => __( 'Min number allowed', 'super' ), 
    'desc' => __( 'Set to 0 to remove limitations.', 'super' )
);
$width = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['width']) ? 0 : $attributes['width']),
    'min' => 0, 
    'max' => 600, 
    'steps' => 10, 
    'name' => __( 'Field width in pixels', 'super' ), 
    'desc' => __( 'Set to 0 to use default CSS width.', 'super' )
);
$height = array(
    'type' => 'slider', 
    'default'=> (!isset($attributes['height']) ? 0 : $attributes['height']),
    'min' => 0, 
    'max' => 600, 
    'steps' => 10, 
    'name' => __( 'Field height in pixels', 'super' ), 
    'desc' => __( 'Set to 0 to use default CSS height.', 'super' )
);
$exclude = array(
    'name'=>__( 'Exclude from email', 'super' ), 
    'desc'=>__( 'You can prevent this data from being send to the form submitter (if the option to send confirmation email has been enabled).', 'super' ), 
    'default'=> (!isset($attributes['exclude']) ? 0 : $attributes['exclude']),
    'type'=>'select', 
    'values'=>array(
        '0'=>__( 'Do not exclude from emails', 'super' ),
        '1'=>__( 'Exclude from confirmation email', 'super' ), 

        // Since version 1.0.4
        '2'=>__( 'Exclude from all emails', 'super' )
    )
);
$error_position = array(
    'name'=>__( 'Error message positioning', 'super' ), 
    'default'=> (!isset($attributes['error_position']) ? '' : $attributes['error_position']),
    'type'=>'select', 
    'values'=>array(
        ''=>__( 'Default positioning', 'super' ), 
        'bottom-right'=>__( 'Bottom right', 'super' ), 
        'bottom-left'=>__( 'Bottom left', 'super' ), 
        'top-right'=>__( 'Top right', 'super' ), 
        'top-left'=>__( 'Top left', 'super' ), 
    )
); 
$error_position_left_only = array(
    'name'=>__( 'Error message positioning', 'super' ), 
    'default'=> (!isset($attributes['error_position']) ? '' : $attributes['error_position']),
    'type'=>'select', 
    'values'=>array(
        ''=>__( 'Default positioning', 'super' ), 
        'bottom-left'=>__( 'Bottom left', 'super' ), 
        'top-left'=>__( 'Top left', 'super' ), 
    )
);         
$outside = array(
    'name' => __( 'Position of use', 'super' ),
    'desc' => __( 'Select where you want to use this HTML shortcode.', 'super' ),
    'type' => 'select',
    'default'=> (!isset($attributes['outside']) ? 0 : $attributes['outside']),
    'values' => array(
        1 => __( 'I want to use this shortcode outside the form', 'super' ),
        0 => __( 'I want to use this shortcode inside the form', 'super' )
    )
);
$styles = array(
    'type' => 'textarea', 
    'default'=> (!isset($attributes['styles']) ? '' : $attributes['styles']),
    'name' => __( 'Extra styles', 'super' ), 
    'desc' => __( 'Use this to add some extra styles for this element.', 'super' ),
);
$conditional_action = array(
    'name'=>__( 'Show or Hide?', 'super' ), 
    'desc'=>__( 'Based on your conditions you can choose to hide or show this field.', 'super' ), 
    'default'=> (!isset($attributes['conditional_action']) ? 'disabled' : $attributes['conditional_action']),
    'type'=>'select',
    'values'=>array(
        'disabled'=>__( 'Disabled (do not use conditional logic)', 'super' ),
        'show'=>__( 'Show', 'super' ),
        'hide'=>__( 'Hide', 'super' ),
    ),
);
$conditional_trigger = array(
    'name'=>__( 'When to Trigger?', 'super' ), 
    'desc'=>__( 'Trigger only when all or one of the below conditions matched their value.', 'super' ), 
    'default'=> (!isset($attributes['conditional_trigger']) ? 'all' : $attributes['conditional_trigger']),
    'type'=>'select',
    'values'=>array(
        'all'=>__( 'All (when all conditions matched)', 'super' ),
        'one'=>__( 'One (when one condition matched)', 'super' ),
    ),
);
$conditional_field_name = array(
    'name'=>__( 'Retrieve value from', 'super' ), 
    'desc'=>__( 'Based on the above selected field value this element will be vissible or hidden.', 'super' ),
    'type' => 'previously_created_fields',
    'default'=> (!isset($attributes['conditional_field']) ? '' : $attributes['conditional_field']),
    'values' => array(
        '' => '- select a field -',
    ),
);        
$conditional_logic = array(
    'default'=> (!isset($attributes['logic']) ? '' : $attributes['logic']),
    'name'=>__( 'Conditional logic', 'super' ), 
    'desc'=>__( 'The logic/method of the validation.', 'super' ),
    'type'=>'select',
    'values'=> array(
        'contains'=>'?? '.__( 'Contains', 'super' ),
        'equal'=>'== '.__( 'Equal', 'super' ),
        'not_equal'=>'!= '.__( 'Not equal', 'super' ),
        'greater_than'=>'> '.__( 'Greater than', 'super' ),
        'less_than'=>'<  '.__( 'Less than', 'super' ),
        'greater_than_or_equal'=>'>= '.__( 'Greater than or equal to', 'super' ),
        'less_than_or_equal'=>'<= '.__( 'Less than or equal to', 'super' ),
    ),
);
$conditional_field_value = array(
    'default'=> (!isset($attributes['value']) ? '' : $attributes['value']),
    'name'=>__( 'Conditional field value', 'super' ), 
    'desc'=>__( 'The value the field needs to have before this field will become visible.', 'super' )
);
$animation = array(
    '' => __( 'No animation', 'super' ),
    'fade-in' => __( 'Fade in without movement', 'super' ), 
    'fade-in-up' => __( 'Fade in down to up', 'super' ), 
    'fade-in-left' => __( 'Fade in right to left', 'super' ),
    'fade-in-right' => __( 'Fade in left to right', 'super' ),
    'fade-in-down' => __( 'Fade in up to down', 'super' )
);
$icon_position = array(
    'default'=> (!isset($attributes['icon_position']) ? 'outside' : $attributes['icon_position']),
    'name'=>__( 'Icon positioning', 'super' ), 
    'desc'=>__( 'How to display your icon.', 'super' ),
    'type'=>'select',
    'values'=> array(
        'inside'=>__( 'Inside the field', 'super' ),
        'outside'=>__( 'Outside the field', 'super' ),
    ),
);
$icon_align = array(
    'default'=> (!isset($attributes['icon_align']) ? 'left' : $attributes['icon_align']),
    'name'=>__( 'Icon alignment', 'super' ), 
    'desc'=>__( 'Align icon to the left or right.', 'super' ),
    'type'=>'select',
    'values'=> array(
        'left'=>__( 'Left', 'super' ),
        'right'=>__( 'Right', 'super' ),
    ),
);
$icon = array(
    'default'=> (!isset($attributes['icon']) ? 'user' : $attributes['icon']),
    'name'=>__( 'Select an Icon', 'super' ), 
    'type'=>'icon',
    'desc'=>__( 'Leave blank if you prefer to not use an icon.', 'super' )
);
$conditional_logic_array = array(
    'name' => __( 'Conditional Logic', 'super' ),
    'fields' => array(
        'conditional_action' => $conditional_action,
        'conditional_trigger' => $conditional_trigger,
        'conditional_items' => array( 
            'name'=>__( 'Conditions', 'super' ), 
            'desc'=>__( 'The conditions that this element should listen to.', 'super' ),
            'type'=>'conditions',
            'default'=> (!isset($attributes['conditional_items']) ? '' : $attributes['conditional_items']),
        )
    )
);