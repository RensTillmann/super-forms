<?php
$array['form_elements'] = array(
    'title' => __( 'Form Elements', 'super-forms' ),   
    'class' => 'super-form-elements',
    'shortcodes' => array(
        'email' => array(
            'name' => __( 'Email Address', 'super-forms' ),
            'icon' => 'envelope-o',
            'predefined' => array(
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'inner' => '',
                    'data' => array(
                        'name' => __( 'email', 'super-forms' ),
                        'email' => __( 'Email address:', 'super-forms' ),
                        'placeholder' => __( 'Your Email Address', 'super-forms' ),
                        'validation' => 'email',
                        'icon_position' => 'outside',
                        'icon_align' => 'left',
                        'icon' => 'envelope-o',
                        'conditional_action' => 'disabled',
                    )
                )            
            ),
            'atts' => array(),
        ),
        'title' => array(
            'name' => __( 'Title', 'super-forms' ),
            'icon' => 'user',
            'predefined' => array(
                array(
                    'tag' => 'dropdown',
                    'group' => 'form_elements',
                    'inner' => '',
                    'data' => array(
                        'dropdown_items' => array(
                            array(
                                'checked' => false,
                                'label' => __( 'Mr.', 'super-forms' ),
                                'value' => __( 'Mr.', 'super-forms' )
                            ),
                            array(
                                'checked' => false,
                                'label' => __( 'Mis.', 'super-forms' ),
                                'value' => __( 'Mis.', 'super-forms' )
                            )
                        ),
                        'name' => __( 'title', 'super-forms' ),
                        'email' => __( 'Title:', 'super-forms' ),
                        'placeholder' => __( '- select your title -', 'super-forms' ),
                        'validation' => 'empty',
                        'icon_position' => 'outside',
                        'icon_align' => 'left',
                        'icon' => 'toggle-down',
                        'conditional_action' => 'disabled',
                    )
                )            
            ),
            'atts' => array(),
        ),
        'first_last_name' => array(
            'name' => __( 'First/Last name', 'super-forms' ),
            'icon' => 'user',
            'predefined' => array(
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'inner' => '',
                    'data' => array(
                        'name' => __( 'first_name', 'super-forms' ),
                        'email' => __( 'First name:', 'super-forms' ),
                        'placeholder' => __( 'Your First Name', 'super-forms' ),
                        'validation' => 'empty',
                        'grouped' => '1',
                        'icon_position' => 'outside',
                        'icon_align' => 'left',
                        'icon' => 'user',
                        'conditional_action' => 'disabled',
                    )
                ),
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'inner' => '',
                    'data' => array(
                        'name' => __( 'last_name', 'super-forms' ),
                        'email' => __( 'Last name:', 'super-forms' ),
                        'placeholder' => __( 'Your Last Name', 'super-forms' ),
                        'validation' => 'empty',
                        'grouped' => '2',
                        'icon' => '',
                        'conditional_action' => 'disabled',
                    )
                )
            ),
            'atts' => array(),
        ),
        'address' => array(
            'name' => __( 'Address', 'super-forms' ),
            'icon' => 'map-marker',
            'predefined' => array(
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'inner' => '',
                    'data' => array(
                        'name' => __( 'address', 'super-forms' ),
                        'email' => __( 'Address:', 'super-forms' ),
                        'placeholder' => __( 'Your Address', 'super-forms' ),
                        'validation' => 'empty',
                        'icon_position' => 'outside',
                        'icon_align' => 'left',
                        'icon' => 'map-marker',
                        'conditional_action' => 'disabled',
                    )
                )
            ),
            'atts' => array(),
        ),
        'zipcode_city_country' => array(
            'name' => __( 'Zipcode & City', 'super-forms' ),
            'icon' => 'map-marker',
            'predefined' => array(
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'inner' => '',
                    'data' => array(
                        'name' => __( 'zipcode', 'super-forms' ),
                        'email' => __( 'Zipcode:', 'super-forms' ),
                        'placeholder' => __( 'Zipcode', 'super-forms' ),
                        'validation' => 'empty',
                        'grouped' => '1',
                        'minlength' => '4',
                        'icon_position' => 'outside',
                        'icon_align' => 'left',
                        'icon' => 'map-marker',
                        'conditional_action' => 'disabled',
                    )
                ),
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'inner' => '',
                    'data' => array(
                        'name' => __( 'city', 'super-forms' ),
                        'email' => __( 'City:', 'super-forms' ),
                        'placeholder' => __( 'City', 'super-forms' ),
                        'validation' => 'empty',
                        'grouped' => '2',
                        'minlength' => '2',
                        'conditional_action' => 'disabled',
                    )
                ),
                array(
                    'tag' => 'countries',
                    'group' => 'form_elements',
                    'inner' => '',
                    'data' => array(
                        'name' => __( 'country', 'super-forms' ),
                        'email' => __( 'Country:', 'super-forms' ),
                        'placeholder' => __( '- select your country -', 'super-forms' ),
                        'validation' => 'none',
                        'icon' => 'globe',
                        'conditional_action' => 'disabled',
                    )
                )
            ),
            'atts' => array(),
        ),
        'text' => array(
            'callback' => 'SUPER_Shortcodes::text',
            'name' => __( 'Text field', 'super-forms' ),
            'icon' => 'list',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='name'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Name'),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder( $attributes, __( 'Your Full Name', 'super-forms' ) ),
                        'value' => array(
                            'default'=> ( !isset( $attributes['value'] ) ? '' : $attributes['value'] ),
                            'name' => __( 'Default value', 'super-forms' ), 
                            'desc' => __( 'Set a default value for this field. {post_id} and {post_title} can be used (leave blank for none)', 'super-forms' )
                        ),
                        'tooltip' => $tooltip,
                        'validation' => $special_validations,
                        'conditional_validation' => $conditional_validation,
                        'conditional_validation_value' => $conditional_validation_value,
                        'may_be_empty' => $may_be_empty,
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'disabled' => $disabled,
                        'grouped' => $grouped,
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'maxnumber' => $maxnumber,
                        'minnumber' => $minnumber,
                        'width' => $width,
                        'wrapper_width' => $wrapper_width,
                        'exclude' => $exclude,
                        'error_position' => $error_position,
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super-forms' ),
                    'fields' => array(
                        'icon_position' => $icon_position,
                        'icon_align' => $icon_align,
                        'icon' => SUPER_Shortcodes::icon($attributes,'user'),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'textarea' => array(
            'callback' => 'SUPER_Shortcodes::textarea',
            'name' => __( 'Text area', 'super-forms' ),
            'icon' => 'list-alt',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='question'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Question'),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes, __( 'Ask us any questions...', 'super-forms' ) ),
                        'value' => array(
                            'default'=> ( !isset( $attributes['value'] ) ? '' : $attributes['value'] ),
                            'name' => __( 'Default value', 'super-forms' ), 
                            'desc' => __( 'Set a default value for this field. {post_id}, {post_title} and {user_****} can be used (leave blank for none)', 'super-forms' )
                        ),
                        'tooltip' => $tooltip,
                        'validation' => $validation_empty,
                        'error' => $error,  
                    )
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'disabled' => $disabled,
                        'grouped' => $grouped,
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'width' => $width,
                        'wrapper_width' => $wrapper_width,
                        'height' => $height,                    
                        'exclude' => $exclude, 
                        'error_position' => $error_position,
                        
                    ),
                ),
                'editor_settings' => array(
                    'name' => __( 'Text Editor Settings', 'super-forms' ),
                    'fields' => array(
                        'editor' => array(
                            'name' => __( 'Enable the WordPress text editor', 'super-forms' ), 
                            'desc' => __( 'Wether to use the WordPress text editor (wp_editor)', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['editor'] ) ? 'false' : $attributes['editor'] ),
                            'type' => 'select', 
                            'values' => array(
                                'false' => __( 'No (disabled)', 'super-forms' ), 
                                'true' => __( 'Yes (enabled)', 'super-forms' ),
                            ),
                            'filter'=>true,
                        ),
                        'media_buttons' => array(
                            'name' => __( 'Enable media upload button', 'super-forms' ), 
                            'desc' => __( 'Whether to display media insert/upload buttons', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['media_buttons'] ) ? 'true' : $attributes['media_buttons'] ),
                            'type' => 'select', 
                            'values' => array(
                                'false' => __( 'No (disabled)', 'super-forms' ), 
                                'true' => __( 'Yes (enabled)', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'editor',
                            'filter_value'=>'true'
                        ),
                        'wpautop' => array(
                            'name' => __( 'Automatically add paragraphs', 'super-forms' ), 
                            'desc' => __( 'Whether to use wpautop for adding in paragraphs', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['wpautop'] ) ? 'true' : $attributes['wpautop'] ),
                            'type' => 'select', 
                            'values' => array(
                                'false' => __( 'Yes (enabled)', 'super-forms' ), 
                                'true' => __( 'No (disabled)', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'editor',
                            'filter_value'=>'true'
                        ),
                        'editor_height' => array(
                            'name' => __( 'Editor height in pixels', 'super-forms' ), 
                            'desc' => __( 'The height to set the editor in pixels', 'super-forms' ), 
                            'type' => 'slider', 
                            'default'=> ( !isset( $attributes['editor_height'] ) ? 100 : $attributes['editor_height'] ),
                            'min' => 0,
                            'max' => 500,
                            'steps' => 10,
                            'filter'=>true,
                            'parent'=>'editor',
                            'filter_value'=>'true'
                        ),
                        'teeny' => array(
                            'name' => __( 'Use minimal editor config', 'super-forms' ), 
                            'desc' => __( 'Whether to output the minimal editor configuration used in PressThis', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['teeny'] ) ? 'false' : $attributes['teeny'] ),
                            'type' => 'select', 
                            'values' => array(
                                'false' => __( 'No (disabled)', 'super-forms' ), 
                                'true' => __( 'Yes (enabled)', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'editor',
                            'filter_value'=>'true'
                        ),
                        'quicktags' => array(
                            'name' => __( 'Load Quicktags', 'super-forms' ), 
                            'desc' => __( 'Disable this to remove your editor\'s Visual and Text tabs', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['quicktags'] ) ? 'true' : $attributes['quicktags'] ),
                            'type' => 'select', 
                            'values' => array(
                                'false' => __( 'No (disabled)', 'super-forms' ), 
                                'true' => __( 'Yes (enabled)', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'editor',
                            'filter_value'=>'true'
                        ),
                        'drag_drop_upload' => array(
                            'name' => __( 'Enable Drag & Drop Upload Support', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['drag_drop_upload'] ) ? 'false' : $attributes['drag_drop_upload'] ),
                            'type' => 'select', 
                            'values' => array(
                                'false' => __( 'No (disabled)', 'super-forms' ), 
                                'true' => __( 'Yes (enabled)', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'editor',
                            'filter_value'=>'true'
                        ),
                    ),
                ),


                'icon' => array(
                    'name' => __( 'Icon', 'super-forms' ),
                    'fields' => array(
                        'icon_position' => $icon_position,
                        'icon_align' => $icon_align,
                        'icon' => SUPER_Shortcodes::icon($attributes,'question'),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array

            ),
        ),
        'dropdown' => array(
            'callback' => 'SUPER_Shortcodes::dropdown',
            'name' => __( 'Dropdown', 'super-forms' ),
            'icon' => 'caret-square-o-down',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'retrieve_method' => array(
                            'name' => __( 'Retrieve method', 'super-forms' ), 
                            'desc' => __( 'Select a method for retrieving items', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method'] ) ? 'custom' : $attributes['retrieve_method'] ),
                            'type' => 'select', 
                            'filter'=>true,
                            'values' => array(
                                'custom' => __( 'Custom items', 'super-forms' ), 
                                'taxonomy' => __( 'Specific taxonomy (categories)', 'super-forms' ),
                                'csv' => __( 'CSV file', 'super-forms' ),
                            )
                        ),
                        'retrieve_method_csv' => array(
                            'name' => __( 'Upload CSV file', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_csv'] ) ? '' : $attributes['retrieve_method_csv'] ),
                            'type' => 'file',
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'csv'
                        ),
                        'retrieve_method_taxonomy' => array(
                            'name' => __( 'Taxonomy slug', 'super-forms' ), 
                            'desc' => __( 'Enter the taxonomy slug name e.g category or product_cat', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_taxonomy'] ) ? 'category' : $attributes['retrieve_method_taxonomy'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'taxonomy'
                        ),
                        'retrieve_method_exclude_taxonomy' => array(
                            'name' => __( 'Exclude a category', 'super-forms' ), 
                            'desc' => __( 'Enter the category ID\'s to exclude seperated by comma\'s', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_exclude_taxonomy'] ) ? '' : $attributes['retrieve_method_exclude_taxonomy'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'taxonomy'
                        ),
                        'retrieve_method_hide_empty' => array(
                            'name' => __( 'Hide empty categories', 'super-forms' ), 
                            'desc' => __( 'Show or hide empty categories', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_hide_empty'] ) ? 0 : $attributes['retrieve_method_hide_empty'] ),
                            'type' => 'select', 
                            'filter'=>true,
                            'values' => array(
                                0 => __( 'Disabled', 'super-forms' ), 
                                1 => __( 'Enabled', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'taxonomy'
                        ),
                        'retrieve_method_parent' => array(
                            'name' => __( 'Based on parent ID', 'super-forms' ), 
                            'desc' => __( 'Retrieve categories by it\'s parent ID (integer only)', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_parent'] ) ? '' : $attributes['retrieve_method_parent'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'taxonomy'
                        ),
                        'dropdown_items' => array(
                            'type' => 'dropdown_items',
                            'default'=> ( !isset( $attributes['dropdown_items'] ) ? 
                                array(
                                    array(
                                        'checked' => false,
                                        'label' => __( 'First choice', 'super-forms' ),
                                        'value' => __( 'first_choice', 'super-forms' )
                                    ),
                                    array(
                                        'checked' => false,
                                        'label' => __( 'Second choice', 'super-forms' ),
                                        'value' => __( 'second_choice', 'super-forms' )
                                    ),
                                    array(
                                        'checked' => false,
                                        'label' => __( 'Third choice', 'super-forms' ),
                                        'value' => __( 'third_choice', 'super-forms' )
                                    )
                                ) : $attributes['dropdown_items']
                            ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'custom'
                        ),
                        'name' => SUPER_Shortcodes::name($attributes, $default='option'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Option'),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes, __( '- select a option -', 'super-forms' ) ),
                        'tooltip' => $tooltip,
                        'validation' => $validation_empty,
                        'error' => $error
                    )
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'grouped' => $grouped,
                        'width' => $width,                   
                        'wrapper_width' => $wrapper_width,
                        'exclude' => $exclude,
                        'error_position' => $error_position_left_only
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super-forms' ),
                    'fields' => array(
                        'icon_position' => $icon_position,
                        'icon_align' => $icon_align,
                        'icon' => SUPER_Shortcodes::icon($attributes,'toggle-down'),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array

            ),
        ),
        'dropdown_item' => array(
            'callback' => 'SUPER_Shortcodes::dropdown_item',
            'hidden' => true,
            'name' => '',
            'icon' => 'section-width',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'label' => array(
                            'name'=>__( 'Label', 'super-forms' ),
                            'default'=> ( !isset( $attributes['label']) ? __( 'Label', 'super-forms' ) : $attributes['label']),
                        ),
                        'value' => array(
                            'name'=>__( 'Value', 'super-forms' ),
                            'default'=> ( !isset( $attributes['value']) ? __( 'Value', 'super-forms' ) : $attributes['value']),
                        ),
                    )
                ),
            ),
        ),
        'checkbox' => array(
            'callback' => 'SUPER_Shortcodes::checkbox',
            'name' => __( 'Check box', 'super-forms' ),
            'icon' => 'check-square-o',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'checkbox_items' => array(
                            'type' => 'checkbox_items',
                            'default'=> ( !isset( $attributes['checkbox_items'] ) ? 
                                array(
                                    array(
                                        'checked' => 'false',
                                        'label' => __( 'First choice', 'super-forms' ),
                                        'value' => __( 'first_choice', 'super-forms' )
                                    ),
                                    array(
                                        'checked' => 'false',
                                        'label' => __( 'Second choice', 'super-forms' ),
                                        'value' => __( 'second_choice', 'super-forms' )
                                    ),
                                    array(
                                        'checked' => 'false',
                                        'label' => __( 'Third choice', 'super-forms' ),
                                        'value' => __( 'third_choice', 'super-forms' )
                                    )
                                ) : $attributes['checkbox_items']
                            ),
                        ),
                        'name' => SUPER_Shortcodes::name($attributes, $default='option'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Option'),
                        'label' => $label,
                        'description'=>$description,
                        'tooltip' => $tooltip,
                        'validation' => $validation_empty,
                        'error' => $error,  
                    )
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'display' => array(
                            'name'=>__( 'Vertical / Horizontal display', 'super-forms' ), 
                            'type' => 'select',
                            'default'=> ( !isset( $attributes['display']) ? 'vertical' : $attributes['display']),
                            'values' => array(
                                'vertical' => __( 'Vertical display ( | )', 'super-forms' ), 
                                'horizontal' => __( 'Horizontal display ( -- )', 'super-forms' ), 
                            ),
                        ),
                        'grouped' => $grouped,                    
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'width' => $width,
                        'wrapper_width' => $wrapper_width,
                        'exclude' => $exclude, 
                        'error_position' => $error_position_left_only,
                        
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super-forms' ),
                    'fields' => array(
                        'icon_position' => $icon_position,
                        'icon_align' => $icon_align,
                        'icon' => SUPER_Shortcodes::icon($attributes,'check-square-o'),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'checkbox_item' => array(
            'callback' => 'SUPER_Shortcodes::checkbox_item',
            'hidden' => true,
            'name' => '',
            'icon' => 'section-width',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'label' => array(
                            'name'=>__( 'Label', 'super-forms' ),
                            'default'=> ( !isset( $attributes['label']) ? __( 'Label', 'super-forms' ) : $attributes['label']),
                        ),
                        'value' => array(
                            'name'=>__( 'Value', 'super-forms' ),
                            'default'=> ( !isset( $attributes['value']) ? __( 'Value', 'super-forms' ) : $attributes['value']),
                        ),
                    )
                ),
            ),
        ),
        'radio' => array(
            'callback' => 'SUPER_Shortcodes::radio',
            'name' => __( 'Radio buttons', 'super-forms' ),
            'icon' => 'dot-circle-o',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'radio_items' => array(
                            'type' => 'radio_items',
                            'default'=> ( !isset( $attributes['radio_items'] ) ? 
                                array(
                                    array(
                                        'checked' => 'false',
                                        'label' => __( 'First choice', 'super-forms' ),
                                        'value' => __( 'first_choice', 'super-forms' )
                                    ),
                                    array(
                                        'checked' => 'false',
                                        'label' => __( 'Second choice', 'super-forms' ),
                                        'value' => __( 'second_choice', 'super-forms' )
                                    ),
                                    array(
                                        'checked' => 'false',
                                        'label' => __( 'Third choice', 'super-forms' ),
                                        'value' => __( 'third_choice', 'super-forms' )
                                    )
                                ) : $attributes['radio_items']
                            ),
                        ),
                        'name' => SUPER_Shortcodes::name($attributes, $default='option'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Option'),
                        'label'=>$label,
                        'description'=>$description,
                        'tooltip' => $tooltip,
                        'validation' => $validation_empty,
                        'error' => $error,  
                    )
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'display' => array(
                            'name'=>__( 'Vertical / Horizontal display', 'super-forms' ), 
                            'type' => 'select',
                            'default'=> ( !isset( $attributes['display']) ? 'vertical' : $attributes['display']),
                            'values' => array(
                                'vertical' => __( 'Vertical display ( | )', 'super-forms' ), 
                                'horizontal' => __( 'Horizontal display ( -- )', 'super-forms' ), 
                            ),
                        ),
                        'grouped' => $grouped,                    
                        'width' => $width,
                        'wrapper_width' => $wrapper_width,
                        'exclude' => $exclude, 
                        'error_position' => $error_position_left_only,
                        
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super-forms' ),
                    'fields' => array(
                        'icon_position' => $icon_position,
                        'icon_align' => $icon_align,
                        'icon' => SUPER_Shortcodes::icon($attributes,'dot-circle-o'),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'radio_item' => array(
            'callback' => 'SUPER_Shortcodes::radio_item',
            'hidden' => true,
            'name' => '',
            'icon' => 'section-width',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'label' => array(
                            'name'=>__( 'Label', 'super-forms' ),
                            'default'=> ( !isset( $attributes['label']) ? __( 'Label', 'super-forms' ) : $attributes['label']),
                        ),
                        'value' => array(
                            'name'=>__( 'Value', 'super-forms' ),
                            'default'=> ( !isset( $attributes['value']) ? __( 'Value', 'super-forms' ) : $attributes['value']),
                        ),
                    )
                ),
            ),
        ),
        'slider' => array(
            'callback' => 'SUPER_Shortcodes::slider_field',
            'name' => __( 'Slider field', 'super-forms' ),
            'icon' => 'sliders',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='amount'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Amount'),
                        'label' => $label,
                        'description'=>$description,
                        'value' => array(
                            'default'=> ( !isset( $attributes['value'] ) ? '0' : $attributes['value'] ),
                            'name' => __( 'Default value', 'super-forms' ), 
                            'desc' => __( 'Set a default value for this field (leave blank for none)', 'super-forms' )
                        ),
                        'format' => array(
                            'default'=> ( !isset( $attributes['format'] ) ? '' : $attributes['format'] ),
                            'name' => __( 'Number format (example: GB / Gygabyte)', 'super-forms' ), 
                            'desc' => __( 'Set a number format e.g: Gygabyte, Kilometers etc. (leave blank for none)', 'super-forms' )
                        ),
                        'currency' => array(
                            'name'=>__( 'Currency', 'super' ), 
                            'desc'=>__( 'Set the currency of or leave empty for no currency e.g: $ or €', 'super' ),
                            'default'=> ( !isset( $attributes['currency'] ) ? '$' : $attributes['currency'] ),
                            'placeholder'=>'$',
                        ),
                        'decimals' => array(
                            'name'=>__( 'Length of decimal', 'super' ), 
                            'desc'=>__( 'Choose a length for your decimals (default = 2)', 'super' ), 
                            'default'=> (!isset($attributes['decimals']) ? '2' : $attributes['decimals']),
                            'type'=>'select', 
                            'values'=>array(
                                '0' => __( '0 decimals', 'super' ),
                                '1' => __( '1 decimal', 'super' ),
                                '2' => __( '2 decimals', 'super' ),
                                '3' => __( '3 decimals', 'super' ),
                                '4' => __( '4 decimals', 'super' ),
                                '5' => __( '5 decimals', 'super' ),
                                '6' => __( '6 decimals', 'super' ),
                                '7' => __( '7 decimals', 'super' ),
                            )
                        ),
                        'decimal_separator' => array(
                            'name'=>__( 'Decimal separator', 'super' ), 
                            'desc'=>__( 'Choose your decimal separator (comma or dot)', 'super' ), 
                            'default'=> (!isset($attributes['decimal_separator']) ? '.' : $attributes['decimal_separator']),
                            'type'=>'select', 
                            'values'=>array(
                                '.' => __( '. (dot)', 'super' ),
                                ',' => __( ', (comma)', 'super' ), 
                            )
                        ),
                        'thousand_separator' => array(
                            'name'=>__( 'Thousand separator', 'super' ), 
                            'desc'=>__( 'Choose your thousand separator (empty, comma or dot)', 'super' ), 
                            'default'=> (!isset($attributes['thousand_separator']) ? ',' : $attributes['thousand_separator']),
                            'type'=>'select', 
                            'values'=>array(
                                '' => __( 'None (empty)', 'super' ),
                                '.' => __( '. (dot)', 'super' ),
                                ',' => __( ', (comma)', 'super' ), 
                            )
                        ),
                        'tooltip' => $tooltip,
                        'validation' => $special_validations,
                        'conditional_validation' => $conditional_validation,
                        'conditional_validation_value' => $conditional_validation_value,
                        'may_be_empty' => $may_be_empty,
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'steps' => array(
                            'type' => 'slider', 
                            'default'=> (!isset($attributes['steps']) ? 1 : $attributes['steps']),
                            'min' => 0,
                            'max' => 100,
                            'steps' => 1,
                            'name' => __( 'The steps the slider makes when sliding', 'super-forms' ), 
                        ),
                        'minnumber' => array(
                            'type' => 'slider', 
                            'default'=> (!isset($attributes['minnumber']) ? 0 : $attributes['minnumber']),
                            'min' => 0,
                            'max' => 100,
                            'steps' => 1,
                            'name' => __( 'The minimum amount', 'super-forms' ), 
                        ),
                        'maxnumber' => array(
                            'type' => 'slider', 
                            'default'=> (!isset($attributes['maxnumber']) ? 100 : $attributes['maxnumber']),
                            'min' => 0,
                            'max' => 100,
                            'steps' => 1,
                            'name' => __( 'The maximum amount', 'super-forms' ), 
                        ),
                        'width' => $width,
                        'wrapper_width' => $wrapper_width,
                        'exclude' => $exclude,
                        'error_position' => $error_position,
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super-forms' ),
                    'fields' => array(
                        'icon_position' => $icon_position,
                        'icon_align' => $icon_align,
                        'icon' => SUPER_Shortcodes::icon($attributes,'user'),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'conditional_item' => array(
            'hidden' => true,
            'name' => '',
            'icon' => 'section-width',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'label' => array(
                            'name'=>__( 'Label', 'super-forms' ),
                            'default'=> ( !isset( $attributes['label']) ? __( 'Label', 'super-forms' ) : $attributes['label']),
                        ),
                        'value' => array(
                            'name'=>__( 'Value', 'super-forms' ),
                            'default'=> ( !isset( $attributes['value']) ? __( 'Value', 'super-forms' ) : $attributes['value']),
                        ),
                    )
                ),
            ),
        ),                
        'file' => array(
            'callback' => 'SUPER_Shortcodes::file',
            'name' => __( 'File upload', 'super-forms' ),
            'icon' => 'download',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='file'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='File'),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes,'Upload your documents...'),
                        'tooltip' => $tooltip,
                        'extensions' => $extensions,
                        'filesize' => array(
                            'name'=>'Max file size in MB',
                            'default'=> ( !isset( $attributes['filesize']) ? 5 : $attributes['filesize']),
                            'type'=>'slider',
                            'min'=>1,
                            'max'=>100,
                            'steps'=>1,
                        ),
                        'error' => $error,
                    )
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'width' => $width,
                        'wrapper_width' => $wrapper_width,
                        'exclude' => $exclude,
                        'error_position' => $error_position,
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super-forms' ),
                    'fields' => array(
                        'icon_position' => $icon_position,
                        'icon_align' => $icon_align,
                        'icon' => SUPER_Shortcodes::icon($attributes,'download'),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'phone' => array(
            'name' => __( 'Phone', 'super-forms' ),
            'icon' => 'phone',
            'predefined' => array(
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'inner' => '',
                    'data' => array(
                        'name' => __( 'phonenumber', 'super-forms' ),
                        'email' => __( 'Phonenumber:', 'super-forms' ),
                        'placeholder' => __( 'Your Phonenumber', 'super-forms' ),
                        'validation' => 'phone',
                        'icon_position' => 'outside',
                        'icon_align' => 'left',
                        'icon' => 'phone',
                        'conditional_action' => 'disabled',
                    )
                )            
            ),
            'atts' => array(),
        ),
        'website_url' => array(
            'name' => __( 'Website URL', 'super-forms' ),
            'icon' => 'link',
            'predefined' => array(
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'inner' => '',
                    'data' => array(
                        'name' => __( 'website', 'super-forms' ),
                        'email' => __( 'Website:', 'super-forms' ),
                        'placeholder' => __( 'http://', 'super-forms' ),
                        'validation' => 'website',
                        'icon_position' => 'outside',
                        'icon_align' => 'left',
                        'icon' => 'link',
                        'conditional_action' => 'disabled',
                    )
                )            
            ),
            'atts' => array(),
        ),
        'date' => array(
            'callback' => 'SUPER_Shortcodes::date',
            'name' => __( 'Date', 'super-forms' ),
            'icon' => 'calendar',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='date'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Date'),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes, __( 'Select a date', 'super-forms' )),
                        'tooltip' => $tooltip,
                        'range' => array(
                            'name'=>__( 'Enter a range', 'super-forms' ), 
                            'desc'=>__( 'Example 100 years in the past and 5 years in the future: -100:+5', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['range']) ? '-100:+5' : $attributes['range']),
                        ),
                        'value' => array(
                            'default'=> ( !isset( $attributes['value'] ) ? '' : $attributes['value'] ),
                            'name' => __( 'Default value', 'super-forms' ), 
                            'desc' => __( 'Set a default value for this field (leave blank for none)', 'super-forms' )
                        ),
                        'format' => array(
                            'name'=>__( 'Date Format', 'super-forms' ), 
                            'desc'=>__( 'Change the date format', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['format']) ? 'dd-mm-yy' : $attributes['format']),
                            'filter'=>true,
                            'type'=>'select', 
                            'values'=>array(
                                'custom' => __( 'Custom date format', 'super-forms' ),
                                'dd-mm-yy' => __( 'European - dd-mm-yy', 'super-forms' ),
                                'mm/dd/yy' => __( 'Default - mm/dd/yy', 'super-forms' ),
                                'yy-mm-dd' => __( 'ISO 8601 - yy-mm-dd', 'super-forms' ),
                                'd M, y' => __( 'Short - d M, y', 'super-forms' ),
                                'd MM, y' => __( 'Medium - d MM, y', 'super-forms' ),
                                'DD, d MM, yy' => __( 'Full - DD, d MM, yy', 'super-forms' ),
                                '&apos;day&apos; d &apos;of&apos; MM &apos;in the year&apos; yy' => __( 'With text - "day" d "of" MM "in the year" yy', 'super-forms' ),
                            )
                        ),
                        'custom_format' => array(
                            'name'=>'Enter a custom Date Format',
                            'default'=> ( !isset( $attributes['custom_format']) ? 'dd-mm-yy' : $attributes['custom_format']),
                            'filter'=>true,
                            'parent'=>'format',
                            'filter_value'=>'custom',    
                        ),
                        'validation' => $validation_empty,
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'disabled' => $disabled,
                        'grouped' => $grouped,
                        'width' => SUPER_Shortcodes::width($attributes, $default=0),
                        'minlength' => SUPER_Shortcodes::minlength($attributes, $default=0, $min=-100, $max=100, $steps=1, __( 'Date range (minimum)', 'super-forms' ), __( 'Amount in days to add or deduct based on current day<br />(set to 0 to remove limitations)', 'super-forms' )),
                        'connected_min' => array(
                            'name'=>__( 'Min. Connect with other datepicker', 'super-forms' ),
                            'desc'=>__( 'Achieve date range with 2 datepickers', 'super-forms' ),
                            'default'=> ( !isset( $attributes['connected_min']) ? '' : $attributes['connected_min']),
                            'type'=>'select',
                            'values'=>array(
                                '' => __( '- Not connected -', 'super-forms' ),
                            )
                        ),
                        'maxlength' => SUPER_Shortcodes::maxlength($attributes, $default=0, $min=-100, $max=100, $steps=1, __( 'Date range (maximum)', 'super-forms' ), __( 'Amount in days to add or deduct based on current day<br />(set to 0 to remove limitations)', 'super-forms' )),
                        'connected_max' => array(
                            'name'=>__( 'Max. Connect with other datepicker', 'super-forms' ),
                            'desc'=>__( 'Achieve date range with 2 datepickers', 'super-forms' ),
                            'default'=> ( !isset( $attributes['connected_max']) ? '' : $attributes['connected_max']),
                            'type'=>'select',
                            'values'=>array(
                                '' => __( '- Not connected -', 'super-forms' ),
                            )
                        ),
                        'exclude' => $exclude,
                        'error_position' => $error_position,
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super-forms' ),
                    'fields' => array(
                        'icon_position' => $icon_position,
                        'icon_align' => $icon_align,
                        'icon' => SUPER_Shortcodes::icon($attributes,'calendar'),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'time' => array(
            'callback' => 'SUPER_Shortcodes::time',
            'name' => __( 'Time', 'super-forms' ),
            'icon' => 'clock-o',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='time'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Time'),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes, __( 'Select a time', 'super-forms' )),
                        'tooltip' => $tooltip,
                        'validation' => $validation_empty,
                        'error' => $error,
                    ),
                ),
                'time_format' => array(
                    'name' => __( 'Time Format', 'super-forms' ),
                    'fields' => array(
                        'format' => array(
                            'name'=>__( 'Choose a Time format', 'super-forms' ),
                            'desc'=>__( 'How times should be displayed in the list and input element.', 'super-forms' ),
                            'type'=>'select',
                            'default'=> ( !isset( $attributes['format']) ? 'H:i' : $attributes['format']),
                            'values'=>array(
                                'H:i'=>'16:59 (Hour:Minutes)',
                                'H:i:s'=>'16:59:59 (Hour:Minutes:Seconds)',
                                'h:i A'=>'01:30 AM (Hour:Minutes Ante/Post meridiem)',
                            ),
                        ),
                        'step' => SUPER_Shortcodes::slider($attributes, $default=15, $min=1, $max=60, $steps=1, __( 'Steps between times in minutes', 'super-forms' ), '', $key='step'),
                        'minlength' => array(
                            'name'=>__( 'The time that should appear first in the dropdown list (Minimum Time)', 'super-forms' ),
                            'desc'=>__( 'Example: 09:00<br />(leave blank to disable this feature)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['minlength']) ? '' : $attributes['minlength']),
                            'type'=>'time',
                        ),
                        'maxlength' => array(
                            'name'=>__( 'The time that should appear last in the dropdown list (Maximum Time)', 'super-forms' ),
                            'desc'=>__( 'Example: 17:00<br />(leave blank to disable this feature)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['maxlength']) ? '' : $attributes['maxlength']),
                            'type'=>'time',
                        ),
                        'range' => array(
                            'name'=>__( 'Disable time options by ranges', 'super-forms' ),
                            'desc'=>__( 'Example:<br />0:00|9:00<br />17:00|0:00<br />(enter each range on a new line)', 'super-forms' ),
                            'type'=>'textarea',
                            'default'=> ( !isset( $attributes['range']) ? '' : $attributes['range']),
                        ),                            
                        'duration' => array(
                            'name'=>__( 'Show or hide the duration time', 'super-forms' ),
                            'desc'=>__( 'The duration time will be calculated based on the time that appears first in it\'s dropdown', 'super-forms' ),
                            'type'=>'select',
                            'default'=> ( !isset( $attributes['duration']) ? 'false' : $attributes['duration']),
                            'values'=>array(
                                'false'=>'Hide duration',
                                'true'=>'Show duration',
                            ),
                        ),
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'disabled' => $disabled,
                        'grouped' => $grouped,
                        'width' => SUPER_Shortcodes::width($attributes, $default=0),
                        'exclude' => $exclude,
                        'error_position' => $error_position,
                        
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super-forms' ),
                    'fields' => array(
                        'icon_position' => $icon_position,
                        'icon_align' => $icon_align,
                        'icon' => SUPER_Shortcodes::icon($attributes,'clock-o'),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'rating' => array(
            'callback' => 'SUPER_Shortcodes::rating',
            'name' => __( 'Rating', 'super-forms' ),
            'icon' => 'star-o',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='rating'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Rating'),
                        'label' => $label,
                        'description'=>$description,
                        'tooltip' => $tooltip,
                        'validation' => $validation_empty,
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'width' => SUPER_Shortcodes::width($attributes, $default=0),
                        'exclude' => $exclude,
                        'error_position' => $error_position,
                        
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super-forms' ),
                    'fields' => array(
                        'icon_position' => $icon_position,
                        'icon_align' => $icon_align,
                        'icon' => SUPER_Shortcodes::icon($attributes,'heart'),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'skype' => array(
            'callback' => 'SUPER_Shortcodes::skype',
            'name' => __( 'Skype', 'super-forms' ),
            'icon' => 'skype',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'username' => array(
                            'name'=>__( 'Enter your Skype Name', 'super-forms' ),
                            'desc'=> __( 'This is should be your Skyp username.', 'super-forms' ),
                            'default'=> ( !isset( $attributes['username']) ? '' : $attributes['username']),
                        ),
                        'method' => array(
                            'name'=>'Choose what you\'d like your button to do',
                            'default'=> ( !isset( $attributes['method']) ? 'call' : $attributes['method']),
                            'type'=>'select', 
                            'values'=>array(
                                'call' => 'Call (starts a call with just a click)', 
                                'chat' => 'Chat (starts a conversation with an instant message)', 
                                'dropdown' => 'Dropdown (allow user to choose between call/chat)', 
                            ),
                        ),
                        'color' => array(
                            'name'=>'Choose your button color',
                            'default'=> ( !isset( $attributes['color']) ? 'blue' : $attributes['color']),
                            'type'=>'select', 
                            'values'=>array(
                                'blue' => 'Blue', 
                                'white' => 'White', 
                            ),
                        ),

                        'size' => array(
                            'name'=>'Choose your button size',
                            'default'=> ( !isset( $attributes['size']) ? 16 : $attributes['size']),
                            'type'=>'select', 
                            'values'=>array(
                                10 => '10px', 
                                12 => '12px', 
                                14 => '14px', 
                                16 => '16px', 
                                24 => '24px', 
                                32 => '32px', 
                            ),
                        ),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'countries' => array(
            'callback' => 'SUPER_Shortcodes::countries',
            'name' => __( 'Countries', 'super-forms' ),
            'icon' => 'globe',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='country'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Country'),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes,'- select your country -'),
                        'tooltip' => $tooltip,
                        'validation' => $special_validations,
                        'conditional_validation' => $conditional_validation,
                        'conditional_validation_value' => $conditional_validation_value,
                        'may_be_empty' => $may_be_empty,
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'width' => $width,
                        'wrapper_width' => $wrapper_width,
                        'exclude' => $exclude,
                        'error_position' => $error_position,
                        
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super-forms' ),
                    'fields' => array(
                        'icon_position' => $icon_position,
                        'icon_align' => $icon_align,
                        'icon' => SUPER_Shortcodes::icon($attributes,'globe'),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'password' => array(
            'callback' => 'SUPER_Shortcodes::password',
            'name' => __( 'Password field', 'super-forms' ),
            'icon' => 'lock',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='password'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Password'),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes,'Password'),
                        'tooltip' => $tooltip,
                        'validation' => $special_validations,
                        'conditional_validation' => $conditional_validation,
                        'conditional_validation_value' => $conditional_validation_value,
                        'may_be_empty' => $may_be_empty,
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'disabled' => $disabled,
                        'grouped' => $grouped,
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'width' => $width,
                        'wrapper_width' => $wrapper_width,
                        'exclude' => $exclude,
                        'error_position' => $error_position,
                        
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super-forms' ),
                    'fields' => array(
                        'icon_position' => $icon_position,
                        'icon_align' => $icon_align,
                        'icon' => SUPER_Shortcodes::icon($attributes,'lock'),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'hidden' => array(
            'callback' => 'SUPER_Shortcodes::hidden',
            'name' => __( 'Hidden field', 'super-forms' ),
            'icon' => 'eye-slash',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='hidden'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Hidden'),
                        'value' => array(
                            'default' => '',
                            'name' => __( 'Hidden value', 'super-forms' ),
                            'desc' => __( 'The value for your hidden field.', 'super-forms' ),
                        ),
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'exclude' => $exclude, 
                    ),
                ),
            ),
        ),
        'image' => array(
            'callback' => 'SUPER_Shortcodes::image',
            'name' => __( 'Image', 'super-forms' ),
            'icon' => 'picture-o',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'image' => array(
                            'name'=>__( 'Image', 'super-forms' ),
                            'default'=> ( !isset( $attributes['image']) ? '' : $attributes['image']),
                            'type'=>'image',
                        ),
                        'width' => array(
                            'type' => 'slider', 
                            'default'=> ( !isset( $attributes['width']) ? 150 : $attributes['width']),
                            'min' => 0, 
                            'max' => 600, 
                            'steps' => 10, 
                            'name' => __( 'Maximum image width in pixels', 'super-forms' ), 
                            'desc' => __( 'Set to 0 to use default CSS width.', 'super-forms' )
                        ),
                        'height' => array(
                            'type' => 'slider', 
                            'default'=> ( !isset( $attributes['height']) ? 250 : $attributes['height']),
                            'min' => 0, 
                            'max' => 600, 
                            'steps' => 10, 
                            'name' => __( 'Maximum image height in pixels', 'super-forms' ), 
                            'desc' => __( 'Set to 0 to use default CSS width.', 'super-forms' )
                        ),
                        'alignment' => array(
                            'name'=>__( 'Image Alignment', 'super-forms' ),
                            'desc'=>__( 'Choose how to align your image', 'super-forms' ),
                            'default'=> ( !isset( $attributes['alignment']) ? 'left' : $attributes['alignment']),
                            'type'=>'select',
                            'values'=>array(
                                'center'=>__( 'Center', 'super-forms' ),
                                'left'=>__( 'Left', 'super-forms' ),
                                'right'=>__( 'Right', 'super-forms' ),
                                ''=>__( 'No alignment', 'super-forms' ),
                            )
                        ),
                        'link' => array(
                            'name'=>__( 'Image Link', 'super-forms' ),
                            'desc'=>__( 'Where should your image link to?', 'super-forms' ),
                            'default'=> ( !isset( $attributes['link']) ? '' : $attributes['link']),
                            'filter'=>true,
                            'type'=>'select',
                            'values'=>array(
                                ''=>__( 'No Link', 'super-forms' ),
                                'custom'=>__( 'Custom URL', 'super-forms' ),
                                'post'=>__( 'Post', 'super-forms' ),
                                'page'=>__( 'Page', 'super-forms' ),
                            )
                        ),
                        'custom_link' => array(
                            'name'=>__( 'Enter a custom URL to link to', 'super-forms' ),
                            'default'=> ( !isset( $attributes['custom_link']) ? '' : $attributes['custom_link']),
                            'parent'=>'link',
                            'filter_value'=>'custom',    
                        ),
                        'post' => array(
                            'name'=>__( 'Select a post to link to', 'super-forms' ),
                            'default'=> ( !isset( $attributes['post']) ? '' : $attributes['post']),
                            'type'=>'select',
                            'values'=>SUPER_Common::list_posts_by_type_array('post'),
                            'parent'=>'link',
                            'filter_value'=>'post',    
                        ),
                        'page' => array(
                            'name'=>__( 'Select a page to link to', 'super-forms' ),
                            'default'=> ( !isset( $attributes['page']) ? '' : $attributes['page']),
                            'type'=>'select',
                            'values'=>SUPER_Common::list_posts_by_type_array('page'),
                            'parent'=>'link',
                            'filter_value'=>'page',    
                        ),
                        'target' => array(
                            'name'=>__( 'Open new tab/window', 'super-forms' ),
                            'default'=> ( !isset( $attributes['target']) ? '' : $attributes['target']),
                            'type'=>'select',
                            'values'=>array(
                                ''=>__( 'Open in same window', 'super-forms' ),
                                '_blank'=>__( 'Open in new window', 'super-forms' ),
                            ),
                            'parent'=>'link',
                            'filter_value'=>'custom,post,page',
                        ),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'html' => array(
            'callback' => 'SUPER_Shortcodes::html',
            'name' => __( 'HTML', 'super-forms' ),
            'icon' => 'file-code-o',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'title' => array(
                            'name'=>__( 'Title', 'super-forms' ),
                            'desc'=>'('.__( 'optional', 'super-forms' ).')',
                            'default'=> ( !isset( $attributes['title']) ? '' : $attributes['title']),
                        ),
                        'subtitle' => array(
                            'name'=>__( 'Sub Title', 'super-forms' ),
                            'desc'=>'('.__( 'optional', 'super-forms' ).')',
                            'default'=> ( !isset( $attributes['subtitle']) ? '' : $attributes['subtitle']),
                        ),
                        'class' => array(
                            'name'=>__( 'Custom class', 'super-forms' ),
                            'desc'=>'('.__( 'Add a custom class to append extra styles', 'super-forms' ).')',
                            'default'=> ( !isset( $attributes['class']) ? '' : $attributes['class']),
                        ),
                        'html' => array(
                            'name'=>__( 'HTML', 'super-forms' ),
                            'type'=>'textarea',
                            'default'=> ( !isset( $attributes['html']) ? 'Your HTML here...' : $attributes['html']),
                        ),

                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'recaptcha' => array(
            'callback' => 'SUPER_Shortcodes::recaptcha',
            'name' => __( 'reCAPTCHA', 'super-forms' ),
            'icon' => 'shield',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'label' => $label, 
                        'description'=>$description,
                        'tooltip' => $tooltip,
                        'align' => array(
                            'name'=>__( 'Alignment', 'super-forms' ),
                            'default'=> ( !isset( $attributes['align']) ? 'right' : $attributes['align']),
                            'type'=>'select', 
                            'values'=>array(
                                'left' => __( 'Align Left', 'super-forms' ),
                                'center' => __( 'Align Center', 'super-forms' ),
                                'right' => __( 'Align Right', 'super-forms' ),
                            ),
                        ),
                        'error' => $error,
                        'error_position' => $error_position,
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'divider' => array(
            'callback' => 'SUPER_Shortcodes::divider',
            'name' => 'Divider',
            'icon' => 'minus',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'color' => array(
                            'name'=>__( 'Divider color', 'super-forms' ),
                            'desc'=>__( 'Choose a custom border color.', 'super-forms' ),
                            'default'=> ( !isset( $attributes['color']) ? '#444444' : $attributes['color']),
                            'type'=>'color',
                        ),
                        'border' => array(
                            'name'=>__( 'Border style', 'super-forms' ),
                            'default'=> ( !isset( $attributes['border']) ? 'single' : $attributes['border']),
                            'type'=>'select', 
                            'filter'=>true,
                            'values'=>array(
                                'single' => __( 'Single', 'super-forms' ),
                                'double' => __( 'Double', 'super-forms' ),
                            ),
                        ),
                        'thickness' => array(
                            'name'=>__( 'Border thickness', 'super-forms' ),
                            'default'=> ( !isset( $attributes['thickness']) ? 1 : $attributes['thickness']),
                            'type'=>'slider',
                            'min'=>1,
                            'max'=>20,
                            'steps'=>1,
                        ),
                        'height' => array(
                            'name'=>__( 'Divider height', 'super-forms' ),
                            'default'=> ( !isset( $attributes['height']) ? 1 : $attributes['height']),
                            'type'=>'slider',
                            'min'=>1,
                            'max'=>20,
                            'steps'=>1,
                        ),
                        'border_style' => array(
                            'name'=>__( 'Border style', 'super-forms' ),
                            'default'=> ( !isset( $attributes['border_style']) ? 'dashed' : $attributes['border_style']),
                            'type'=>'select', 
                            'values'=>array(
                                'solid' => __( 'Solid', 'super-forms' ),
                                'dotted' => __( 'Dotted', 'super-forms' ),
                                'dashed' => __( 'Dashed', 'super-forms' ),
                            ),
                        ),
                        'width' => array(
                            'name'=>__( 'Divider weight', 'super-forms' ),
                            'desc'=>__( 'Define the width for the divider.', 'super-forms' ),
                            'default'=> ( !isset( $attributes['width']) ? '100' : $attributes['width']),
                            'type'=>'select', 
                            'filter'=>true,
                            'values'=>array(
                                '100' => '100% '.__( 'width', 'super-forms' ),
                                '75' => '75% '.__( 'width', 'super-forms' ),
                                '50' => '50% '.__( 'width', 'super-forms' ),
                                '25' => '25% '.__( 'width', 'super-forms' ),
                                'custom' => __( 'Custom width in pixels', 'super-forms' ),
                            )
                        ),
                        'custom_width' => array(
                            'name'=>__( 'Divider custom width', 'super-forms' ),
                            'desc'=>__( 'Define a custom width for the divider. Use a pixel value. eg: 150px', 'super-forms' ),
                            'default'=> ( !isset( $attributes['custom_width']) ? '150px' : $attributes['custom_width']),
                            'type'=>'text', 
                            'parent'=>'width',
                            'filter_value'=>'custom',
                            'filter'=>true,
                        ),
                        'align' => array(
                            'name'=>__( 'Divider alignment', 'super-forms' ),
                            'default'=> ( !isset( $attributes['align']) ? 'left' : $attributes['align']),
                            'type'=>'select', 
                            'values'=>array(
                                'left' => __( 'Align Left', 'super-forms' ),
                                'center' => __( 'Align Center', 'super-forms' ),
                                'right' => __( 'Align Right', 'super-forms' ),
                            ),
                        ),
                        'back' => array(
                            'name'=>__( 'Back to top button', 'super-forms' ),
                            'default'=> ( !isset( $attributes['back']) ? '0' : $attributes['back']),
                            'type'=>'select', 
                            'values'=>array(
                                '0' => __( 'Hide back to top button', 'super-forms' ),
                                '1' => __( 'Show back to top button', 'super-forms' ),
                            ),
                        ),
                    ),
                ),
                'padding' => array(
                    'name' => __( 'Padding', 'super-forms' ),
                    'fields' => array(
                        'padding_top' => array(
                            'name'=>__( 'Padding top', 'super-forms' ),
                            'default'=> ( !isset( $attributes['padding_top']) ? 20 : $attributes['padding_top']),
                            'type'=>'slider',
                            'min'=>0,
                            'max'=>100,
                            'steps'=>5,
                        ),
                        'padding_bottom' => array(
                            'name'=>__( 'Padding bottom', 'super-forms' ),
                            'default'=> ( !isset( $attributes['padding_bottom']) ? 20 : $attributes['padding_bottom']),
                            'type'=>'slider',
                            'min'=>0,
                            'max'=>100,
                            'steps'=>5,
                        ),
                    ),
                ),                                              
            ),
        ),
        'spacer' => array(
            'callback' => 'SUPER_Shortcodes::spacer',
            'name' => 'Spacer',
            'icon' => 'arrows-v',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'height' => array(
                            'name'=>'Height in pixels', 
                            'default'=> ( !isset( $attributes['height']) ? 50 : $attributes['height']),
                            'type'=>'slider', 
                            'min' => 0, 
                            'max' => 200, 
                            'steps' => 10,
                        ),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'button' => array(
            'callback' => 'SUPER_Shortcodes::button',
            'name' => 'Button',
            'icon' => 'hand-o-up',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => array(
                            'name'=>__( 'Button name', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['name'] ) ? __( 'Submit', 'super-forms' ) : $attributes['name'] ),
                        ),
                        'link' => array(
                            'name'=>__( 'Button URL', 'super-forms' ),
                            'desc'=>__( 'Where should your image link to?', 'super-forms' ),
                            'default'=> ( !isset( $attributes['link']) ? '' : $attributes['link']),
                            'type'=>'select',
                            'values'=>array(
                                ''=>__( 'None', 'super-forms' ),
                                'custom'=>__( 'Custom URL', 'super-forms' ),
                                'post'=>__( 'Post', 'super-forms' ),
                                'page'=>__( 'Page', 'super-forms' ),
                            ),
                            'filter'=>true,
                        ),
                        'custom_link' => array(
                            'name'=>__( 'Enter a custom URL to link to', 'super-forms' ),
                            'default'=> ( !isset( $attributes['custom_link']) ? '' : $attributes['custom_link']),
                            'parent'=>'link',
                            'filter_value'=>'custom',
                            'filter'=>true,
                        ),
                        'post' => array(
                            'name'=>__( 'Select a post to link to', 'super-forms' ),
                            'default'=> ( !isset( $attributes['post']) ? '' : $attributes['post']),
                            'type'=>'select',
                            'values'=>SUPER_Common::list_posts_by_type_array('post'),
                            'parent'=>'link',
                            'filter_value'=>'post',
                            'filter'=>true,  
                        ),
                        'page' => array(
                            'name'=>__( 'Select a page to link to', 'super-forms' ),
                            'default'=> ( !isset( $attributes['page']) ? '' : $attributes['page']),
                            'type'=>'select',
                            'values'=>SUPER_Common::list_posts_by_type_array('page'),
                            'parent'=>'link',
                            'filter_value'=>'page',
                            'filter'=>true,
                        ),
                        'target' => array(
                            'name'=>__( 'Open new tab/window', 'super-forms' ),
                            'default'=> ( !isset( $attributes['target']) ? '' : $attributes['target']),
                            'type'=>'select',
                            'values'=>array(
                                ''=>__( 'Open in same window', 'super-forms' ),
                                '_blank'=>__( 'Open in new window', 'super-forms' ),
                            ),
                            'parent'=>'link',
                            'filter_value'=>'custom,post,page',
                            'filter'=>true,
                        ),
                    ),
                ),
                'colors' => array(
                    'name' => __( 'Colors', 'super-forms' ),
                    'fields' => array(
                        'custom_colors' => array(
                            'name'=>__( 'Enable custom settings', 'super-forms' ),
                            'desc'=>__( 'Use custom button settings or the default form button settings?', 'super-forms' ),
                            'default'=> ( !isset( $attributes['custom_colors']) ? '' : $attributes['custom_colors']),
                            'type'=>'select',
                            'values'=>array(
                                ''=>__( 'Disabled (use default form settings)', 'super-forms' ),
                                'custom'=>__( 'Enabled (use custom button settings)', 'super-forms' ),
                            ),
                            'filter'=>true,
                        ),
                        'colors' => array(
                            'name' => __('Button Colors', 'super-forms' ),
                            'type'=>'multicolor', 
                            'colors'=>array(
                                'color'=>array(
                                    'label'=>'Button background color',
                                    'default'=> ( !isset( $attributes['color']) ? '#f26c68' : $attributes['color']),
                                ),
                                'color_hover'=>array(
                                    'label'=>'Button background color hover',
                                    'default'=> ( !isset( $attributes['color_hover']) ? '#444444' : $attributes['color_hover']),
                                ),
                                'font'=>array(
                                    'label'=>'Button font color',
                                    'default'=> ( !isset( $attributes['font']) ? '#ffffff' : $attributes['font']),
                                ),
                                'font_hover'=>array(
                                    'label'=>'Button font color hover',
                                    'default'=> ( !isset( $attributes['font_hover']) ? '#ffffff' : $attributes['font_hover']),
                                ),                            
                            ),
                            'parent'=>'custom_colors',
                            'filter_value'=>'custom',
                            'filter'=>true,
                        ),
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'custom_advanced' => array(
                            'name'=>__( 'Enable custom settings', 'super-forms' ),
                            'desc'=>__( 'Use custom button settings or the default form button settings?', 'super-forms' ),
                            'default'=> ( !isset( $attributes['custom_advanced']) ? '' : $attributes['custom_advanced']),
                            'type'=>'select',
                            'values'=>array(
                                ''=>__( 'Disabled (use default form settings)', 'super-forms' ),
                                'custom'=>__( 'Enabled (use custom button settings)', 'super-forms' ),
                            ),
                            'filter'=>true,
                        ),
                        'radius' => array(
                            'name'=> __('Button radius', 'super-forms' ),
                            'default'=> ( !isset( $attributes['radius']) ? 'square' : $attributes['radius']),
                            'type'=>'select',
                            'values'=>array(
                                'rounded'=>'Rounded',
                                'square'=>'Square',
                                'full-rounded'=>'Full Rounded',
                            ),
                            'parent'=>'custom_advanced',
                            'filter_value'=>'custom',
                            'filter'=>true,

                        ),
                        'type' => array(
                            'name'=> __('Button type', 'super-forms' ),
                            'default'=> ( !isset( $attributes['type']) ? 'flat' : $attributes['type']),
                            'type'=>'select',
                            'values'=>array(
                                '3d'=>'3D Button',
                                '2d'=>'2D Button',
                                'flat'=>'Flat Button',
                                'outline'=>'Outline Button',
                                'diagonal'=>'Diagonal Button',
                            ),
                            'parent'=>'custom_advanced',
                            'filter_value'=>'custom',
                            'filter'=>true,
                        ),
                        'size' => array(
                            'name'=> __('Button size', 'super-forms' ),
                            'default'=> ( !isset( $attributes['size']) ? 'medium' : $attributes['size']),
                            'type'=>'select', 
                            'values'=>array(
                                'mini' => 'Mini', 
                                'tiny' => 'Tiny', 
                                'small' => 'Small', 
                                'medium' => 'Medium', 
                                'large' => 'Large', 
                                'big' => 'Big', 
                                'huge' => 'Huge', 
                                'massive' => 'Massive', 
                            ),
                            'parent'=>'custom_advanced',
                            'filter_value'=>'custom',
                            'filter'=>true,
                        ),
                        'align' => array(
                            'name'=> __('Button position', 'super-forms' ),
                            'default'=> ( !isset( $attributes['align']) ? 'left' : $attributes['align']),
                            'type'=>'select', 
                            'values'=>array(
                                'left' => 'Align Left', 
                                'center' => 'Align Center', 
                                'right' => 'Align Right', 
                            ),
                            'parent'=>'custom_advanced',
                            'filter_value'=>'custom',
                            'filter'=>true,
                        ), 
                        'width' => array(
                            'name'=> __('Button width', 'super-forms' ),
                            'default'=> ( !isset( $attributes['width']) ? 'auto' : $attributes['width']),
                            'type'=>'select', 
                            'values'=>array(
                                'auto' => 'Auto', 
                                'fullwidth' => 'Fullwidth', 
                            ),
                            'parent'=>'custom_advanced',
                            'filter_value'=>'custom',
                            'filter'=>true,
                        ),
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super-forms' ),
                    'fields' => array(
                        'custom_icon' => array(
                            'name'=>__( 'Enable custom settings', 'super-forms' ),
                            'desc'=>__( 'Use custom button settings or the default form button settings?', 'super-forms' ),
                            'default'=> ( !isset( $attributes['custom_icon']) ? '' : $attributes['custom_icon']),
                            'type'=>'select',
                            'values'=>array(
                                ''=>__( 'Disabled (use default form settings)', 'super-forms' ),
                                'custom'=>__( 'Enabled (use custom button settings)', 'super-forms' ),
                            ),
                            'filter'=>true,
                        ),
                        'icon_option' => array(
                            'name'=> __('Button icon position', 'super-forms' ),
                            'default'=> ( !isset( $attributes['icon_option']) ? 'none' : $attributes['icon_option']),
                            'type'=>'select', 
                            'values'=>array(
                                'none' => 'No icon', 
                                'left' => 'Left icon', 
                                'right' => 'Right icon', 
                            ),
                            'filter'=>true,
                        ),
                        'icon_visibility' => array(
                            'name'=> __('Button icon visibility', 'super-forms' ),
                            'default'=> ( !isset( $attributes['icon_visibility']) ? 'visible' : $attributes['icon_visibility']),
                            'parent'=>'icon_option',
                            'filter_value'=>'left,right',
                            'type'=>'select', 
                            'values'=>array(
                                'visible' => 'Always Visible', 
                                'hidden' => 'Visible on hover (mouseover)', 
                            ),
                            'filter'=>true,
                        ),
                        'icon_animation' => array(
                            'name'=> __('Button icon animation', 'super-forms' ),
                            'default'=> ( !isset( $attributes['icon_animation']) ? 'horizontal' : $attributes['icon_animation']),
                            'parent'=>'icon_option',
                            'filter_value'=>'left,right',
                            'type'=>'select', 
                            'values'=>array(
                                'horizontal' => 'Horizontal animation', 
                                'vertical' => 'Vertical animation', 
                            ),
                            'filter'=>true,
                        ),                                
                        'icon' => array(
                            'name'=> __('Button icon', 'super-forms' ),
                            'default'=> ( !isset( $attributes['icon']) ? '' : $attributes['icon']),
                            'type'=>'icon',
                            'parent'=>'icon_option',
                            'filter_value'=>'left,right',
                            'filter'=>true,
                        ),
                    ),
                ),
            ),
        ),
    )
);