<?php
$array['form_elements'] = array(
    'title' => __( 'Form Elements', 'super' ),   
    'class' => 'super-form-elements',
    'shortcodes' => array(
        'title' => array(
            'name' => __( 'Title', 'super' ),
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
                                'label' => __( 'Mr.', 'super' ),
                                'value' => __( 'Mr.', 'super' )
                            ),
                            array(
                                'checked' => false,
                                'label' => __( 'Mis.', 'super' ),
                                'value' => __( 'Mis.', 'super' )
                            )
                        ),
                        'name' => __( 'title', 'super' ),
                        'email' => __( 'Title:', 'super' ),
                        'placeholder' => __( '- select your title -', 'super' ),
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
            'name' => __( 'First/Last name', 'super' ),
            'icon' => 'user',
            'predefined' => array(
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'inner' => '',
                    'data' => array(
                        'name' => __( 'first_name', 'super' ),
                        'email' => __( 'First name:', 'super' ),
                        'placeholder' => __( 'Your First Name', 'super' ),
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
                        'name' => __( 'last_name', 'super' ),
                        'email' => __( 'Last name:', 'super' ),
                        'placeholder' => __( 'Your Last Name', 'super' ),
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
            'name' => __( 'Address', 'super' ),
            'icon' => 'map-marker',
            'predefined' => array(
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'inner' => '',
                    'data' => array(
                        'name' => __( 'address', 'super' ),
                        'email' => __( 'Address:', 'super' ),
                        'placeholder' => __( 'Your Address', 'super' ),
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
            'name' => __( 'Zipcode & City', 'super' ),
            'icon' => 'map-marker',
            'predefined' => array(
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'inner' => '',
                    'data' => array(
                        'name' => __( 'zipcode', 'super' ),
                        'email' => __( 'Zipcode:', 'super' ),
                        'placeholder' => __( 'Zipcode', 'super' ),
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
                        'name' => __( 'city', 'super' ),
                        'email' => __( 'City:', 'super' ),
                        'placeholder' => __( 'City', 'super' ),
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
                        'name' => __( 'country', 'super' ),
                        'email' => __( 'Country:', 'super' ),
                        'placeholder' => __( '- select your country -', 'super' ),
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
            'name' => __( 'Text field', 'super' ),
            'icon' => 'list',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='name'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Name'),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes,'Your Full Name'),
                        'tooltip' => $tooltip,
                        'validation' => $validation_all,
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'width' => $width,
                        'exclude' => $exclude,
                        'error_position' => $error_position,
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super' ),
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
            'name' => __( 'Text area', 'super' ),
            'icon' => 'list-alt',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='question'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Question'),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes,'Ask us any questions...'),
                        'tooltip' => $tooltip,
                        'validation' => $validation_empty,
                        'error' => $error,  
                    )
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'width' => $width,
                        'height' => $height,                    
                        'exclude' => $exclude, 
                        'error_position' => $error_position,
                        
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super' ),
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
            'name' => __( 'Dropdown', 'super' ),
            'icon' => 'caret-square-o-down',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'dropdown_items' => array(
                            'type' => 'dropdown_items',
                            'default'=> ( !isset( $attributes['dropdown_items'] ) ? 
                                array(
                                    array(
                                        'checked' => false,
                                        'label' => __( 'First choice', 'super' ),
                                        'value' => __( 'first_choice', 'super' )
                                    ),
                                    array(
                                        'checked' => false,
                                        'label' => __( 'Second choice', 'super' ),
                                        'value' => __( 'second_choice', 'super' )
                                    ),
                                    array(
                                        'checked' => false,
                                        'label' => __( 'Third choice', 'super' ),
                                        'value' => __( 'third_choice', 'super' )
                                    )
                                ) : $attributes['dropdown_items']
                            ),
                        ),
                        'name' => SUPER_Shortcodes::name($attributes, $default='option'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Option'),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes,'- select a option -'),
                        'tooltip' => $tooltip,
                        'validation' => $validation_empty,
                        'error' => $error
                    )
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super' ),
                    'fields' => array(
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'grouped' => $grouped,
                        'width' => $width,                   
                        'exclude' => $exclude,
                        'error_position' => $error_position_left_only
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super' ),
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
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'label' => array(
                            'name'=>__( 'Label', 'super' ),
                            'default'=> (!isset($attributes['label']) ? __( 'Label', 'super' ) : $attributes['label']),
                        ),
                        'value' => array(
                            'name'=>__( 'Value', 'super' ),
                            'default'=> (!isset($attributes['value']) ? __( 'Value', 'super' ) : $attributes['value']),
                        ),
                    )
                ),
            ),
        ),
        'checkbox' => array(
            'callback' => 'SUPER_Shortcodes::checkbox',
            'name' => __( 'Check box', 'super' ),
            'icon' => 'check-square-o',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'checkbox_items' => array(
                            'type' => 'checkbox_items',
                            'default'=> ( !isset( $attributes['checkbox_items'] ) ? 
                                array(
                                    array(
                                        'checked' => 'false',
                                        'label' => __( 'First choice', 'super' ),
                                        'value' => __( 'first_choice', 'super' )
                                    ),
                                    array(
                                        'checked' => 'false',
                                        'label' => __( 'Second choice', 'super' ),
                                        'value' => __( 'second_choice', 'super' )
                                    ),
                                    array(
                                        'checked' => 'false',
                                        'label' => __( 'Third choice', 'super' ),
                                        'value' => __( 'third_choice', 'super' )
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
                    'name' => __( 'Advanced', 'super' ),
                    'fields' => array(
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'display' => array(
                            'name'=>__( 'Vertical / Horizontal display', 'super' ), 
                            'type' => 'select',
                            'default'=> (!isset($attributes['display']) ? 'vertical' : $attributes['display']),
                            'values' => array(
                                'vertical' => __( 'Vertical display ( | )', 'super' ), 
                                'horizontal' => __( 'Horizontal display ( -- )', 'super' ), 
                            ),
                        ),
                        'grouped' => $grouped,                    
                        'width' => $width,
                        'exclude' => $exclude, 
                        'error_position' => $error_position_left_only,
                        
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super' ),
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
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'label' => array(
                            'name'=>__( 'Label', 'super' ),
                            'default'=> (!isset($attributes['label']) ? __( 'Label', 'super' ) : $attributes['label']),
                        ),
                        'value' => array(
                            'name'=>__( 'Value', 'super' ),
                            'default'=> (!isset($attributes['value']) ? __( 'Value', 'super' ) : $attributes['value']),
                        ),
                    )
                ),
            ),
        ),
        'radio' => array(
            'callback' => 'SUPER_Shortcodes::radio',
            'name' => __( 'Radio buttons', 'super' ),
            'icon' => 'dot-circle-o',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'radio_items' => array(
                            'type' => 'radio_items',
                            'default'=> ( !isset( $attributes['radio_items'] ) ? 
                                array(
                                    array(
                                        'checked' => 'false',
                                        'label' => __( 'First choice', 'super' ),
                                        'value' => __( 'first_choice', 'super' )
                                    ),
                                    array(
                                        'checked' => 'false',
                                        'label' => __( 'Second choice', 'super' ),
                                        'value' => __( 'second_choice', 'super' )
                                    ),
                                    array(
                                        'checked' => 'false',
                                        'label' => __( 'Third choice', 'super' ),
                                        'value' => __( 'third_choice', 'super' )
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
                    'name' => __( 'Advanced', 'super' ),
                    'fields' => array(
                        'display' => array(
                            'name'=>__( 'Vertical / Horizontal display', 'super' ), 
                            'type' => 'select',
                            'default'=> (!isset($attributes['display']) ? 'vertical' : $attributes['display']),
                            'values' => array(
                                'vertical' => __( 'Vertical display ( | )', 'super' ), 
                                'horizontal' => __( 'Horizontal display ( -- )', 'super' ), 
                            ),
                        ),
                        'grouped' => $grouped,                    
                        'width' => $width,
                        'exclude' => $exclude, 
                        'error_position' => $error_position_left_only,
                        
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super' ),
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
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'label' => array(
                            'name'=>__( 'Label', 'super' ),
                            'default'=> (!isset($attributes['label']) ? __( 'Label', 'super' ) : $attributes['label']),
                        ),
                        'value' => array(
                            'name'=>__( 'Value', 'super' ),
                            'default'=> (!isset($attributes['value']) ? __( 'Value', 'super' ) : $attributes['value']),
                        ),
                    )
                ),
            ),
        ),
        'conditional_item' => array(
            'hidden' => true,
            'name' => '',
            'icon' => 'section-width',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'label' => array(
                            'name'=>__( 'Label', 'super' ),
                            'default'=> (!isset($attributes['label']) ? __( 'Label', 'super' ) : $attributes['label']),
                        ),
                        'value' => array(
                            'name'=>__( 'Value', 'super' ),
                            'default'=> (!isset($attributes['value']) ? __( 'Value', 'super' ) : $attributes['value']),
                        ),
                    )
                ),
            ),
        ),                
        'file' => array(
            'callback' => 'SUPER_Shortcodes::file',
            'name' => __( 'File upload', 'super' ),
            'icon' => 'download',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
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
                            'default'=> (!isset($attributes['filesize']) ? 5 : $attributes['filesize']),
                            'type'=>'slider',
                            'min'=>1,
                            'max'=>100,
                            'steps'=>1,
                        ),
                        'error' => $error,
                    )
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'width' => $width,
                        'exclude' => $exclude,
                        'error_position' => $error_position,
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super' ),
                    'fields' => array(
                        'icon_position' => $icon_position,
                        'icon_align' => $icon_align,
                        'icon' => SUPER_Shortcodes::icon($attributes,'download'),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'email' => array(
            'name' => __( 'Email', 'super' ),
            'icon' => 'envelope-o',
            'predefined' => array(
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'inner' => '',
                    'data' => array(
                        'name' => __( 'email', 'super' ),
                        'email' => __( 'Email:', 'super' ),
                        'placeholder' => __( 'Your Email Address', 'super' ),
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
        'email' => array(
            'name' => __( 'Phone', 'super' ),
            'icon' => 'phone',
            'predefined' => array(
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'inner' => '',
                    'data' => array(
                        'name' => __( 'phonenumber', 'super' ),
                        'email' => __( 'Phonenumber:', 'super' ),
                        'placeholder' => __( 'Your Phonenumber', 'super' ),
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
            'name' => __( 'Website URL', 'super' ),
            'icon' => 'link',
            'predefined' => array(
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'inner' => '',
                    'data' => array(
                        'name' => __( 'website', 'super' ),
                        'email' => __( 'Website:', 'super' ),
                        'placeholder' => __( 'http://', 'super' ),
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
            'name' => __( 'Date', 'super' ),
            'icon' => 'calendar',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='date'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Date'),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes, __( 'Select a date', 'super' )),
                        'tooltip' => $tooltip,
                        'format' => array(
                            'name'=>__( 'Date Format', 'super' ), 
                            'desc'=>__( 'Change the date format', 'super' ), 
                            'default'=> (!isset($attributes['format']) ? 'mm/dd/yy' : $attributes['format']),
                            'type'=>'select', 
                            'values'=>array(
                                'mm/dd/yy' => __( 'Default - mm/dd/yy', 'super' ),
                                'yy-mm-dd' => __( 'ISO 8601 - yy-mm-dd', 'super' ),
                                'd M, y' => __( 'Short - d M, y', 'super' ),
                                'd MM, y' => __( 'Medium - d MM, y', 'super' ),
                                'DD, d MM, yy' => __( 'Full - DD, d MM, yy', 'super' ),
                                '&apos;day&apos; d &apos;of&apos; MM &apos;in the year&apos; yy' => __( 'With text - "day" d "of" MM "in the year" yy', 'super' ),
                            )
                        ),
                        'validation' => $validation_empty,
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'width' => SUPER_Shortcodes::width($attributes, $default=0),
                        'minlength' => SUPER_Shortcodes::minlength($attributes, $default=0, $min=-100, $max=100, $steps=1, __( 'Date range (minimum)', 'super' ), __( 'Amount in days to add or deduct based on current day<br />(set to 0 to remove limitations)', 'super' )),
                        'maxlength' => SUPER_Shortcodes::maxlength($attributes, $default=0, $min=-100, $max=100, $steps=1, __( 'Date range (maximum)', 'super' ), __( 'Amount in days to add or deduct based on current day<br />(set to 0 to remove limitations)', 'super' )),
                        'exclude' => $exclude,
                        'error_position' => $error_position,
                        
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super' ),
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
            'name' => __( 'Time', 'super' ),
            'icon' => 'clock-o',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='time'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Time'),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes, __( 'Select a time', 'super' )),
                        'tooltip' => $tooltip,
                        'validation' => $validation_empty,
                        'error' => $error,
                    ),
                ),
                'time_format' => array(
                    'name' => __( 'Time Format', 'super' ),
                    'fields' => array(
                        'format' => array(
                            'name'=>__( 'Choose a Time format', 'super' ),
                            'desc'=>__( 'How times should be displayed in the list and input element.', 'super' ),
                            'type'=>'select',
                            'default'=> (!isset($attributes['format']) ? 'H:i' : $attributes['format']),
                            'values'=>array(
                                'H:i'=>'16:59 (Hour:Minutes)',
                                'H:i:s'=>'16:59:59 (Hour:Minutes:Seconds)',
                                'h:i A'=>'01:30 AM (Hour:Minutes Ante/Post meridiem)',
                            ),
                        ),
                        'step' => SUPER_Shortcodes::slider($attributes, $default=15, $min=1, $max=60, $steps=1, __( 'Steps between times in minutes', 'super' ), '', $key='step'),
                        'minlength' => array(
                            'name'=>__( 'The time that should appear first in the dropdown list (Minimum Time)', 'super' ),
                            'desc'=>__( 'Example: 09:00<br />(leave blank to disable this feature)', 'super' ),
                            'default'=> (!isset($attributes['minlength']) ? '' : $attributes['minlength']),
                            'type'=>'time',
                        ),
                        'maxlength' => array(
                            'name'=>__( 'The time that should appear last in the dropdown list (Maximum Time)', 'super' ),
                            'desc'=>__( 'Example: 17:00<br />(leave blank to disable this feature)', 'super' ),
                            'default'=> (!isset($attributes['maxlength']) ? '' : $attributes['maxlength']),
                            'type'=>'time',
                        ),
                        'range' => array(
                            'name'=>__( 'Disable time options by ranges', 'super' ),
                            'desc'=>__( 'Example:<br />0:00|9:00<br />17:00|0:00<br />(enter each range on a new line)', 'super' ),
                            'type'=>'textarea',
                            'default'=> (!isset($attributes['range']) ? '' : $attributes['range']),
                        ),                            
                        'duration' => array(
                            'name'=>__( 'Show or hide the duration time', 'super' ),
                            'desc'=>__( 'The duration time will be calculated based on the time that appears first in it\'s dropdown', 'super' ),
                            'type'=>'select',
                            'default'=> (!isset($attributes['duration']) ? 'false' : $attributes['duration']),
                            'values'=>array(
                                'false'=>'Hide duration',
                                'true'=>'Show duration',
                            ),
                        ),
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'width' => SUPER_Shortcodes::width($attributes, $default=0),
                        'exclude' => $exclude,
                        'error_position' => $error_position,
                        
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super' ),
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
            'name' => __( 'Rating', 'super' ),
            'icon' => 'star-o',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
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
                    'name' => __( 'Advanced', 'super' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'width' => SUPER_Shortcodes::width($attributes, $default=0),
                        'exclude' => $exclude,
                        'error_position' => $error_position,
                        
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super' ),
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
            'name' => __( 'Skype', 'super' ),
            'icon' => 'skype',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'username' => array(
                            'name'=>__( 'Enter your Skype Name', 'super' ),
                            'desc'=> __( 'This is should be your Skyp username.', 'super' ),
                            'default'=> (!isset($attributes['username']) ? '' : $attributes['username']),
                        ),
                        'method' => array(
                            'name'=>'Choose what you\'d like your button to do',
                            'default'=> (!isset($attributes['method']) ? 'call' : $attributes['method']),
                            'type'=>'select', 
                            'values'=>array(
                                'call' => 'Call (starts a call with just a click)', 
                                'chat' => 'Chat (starts a conversation with an instant message)', 
                                'dropdown' => 'Dropdown (allow user to choose between call/chat)', 
                            ),
                        ),
                        'color' => array(
                            'name'=>'Choose your button color',
                            'default'=> (!isset($attributes['color']) ? 'blue' : $attributes['color']),
                            'type'=>'select', 
                            'values'=>array(
                                'blue' => 'Blue', 
                                'white' => 'White', 
                            ),
                        ),

                        'size' => array(
                            'name'=>'Choose your button size',
                            'default'=> (!isset($attributes['size']) ? 16 : $attributes['size']),
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
            'name' => __( 'Countries', 'super' ),
            'icon' => 'globe',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='country'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Country'),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes,'- select your country -'),
                        'tooltip' => $tooltip,
                        'validation' => $validation_all,
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'width' => $width,
                        'exclude' => $exclude,
                        'error_position' => $error_position,
                        
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super' ),
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
            'name' => __( 'Password field', 'super' ),
            'icon' => 'lock',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='password'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Password'),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes,'Password'),
                        'tooltip' => $tooltip,
                        'validation' => $validation_all,
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'width' => $width,
                        'exclude' => $exclude,
                        'error_position' => $error_position,
                        
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super' ),
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
            'name' => __( 'Hidden field', 'super' ),
            'icon' => 'unlock-alt',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='hidden'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Hidden'),
                        'value' => array(
                            'default' => '',
                            'name' => 'Hidden value',
                            'desc' => 'The value for your hidden field.',
                        ),
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super' ),
                    'fields' => array(
                        'exclude' => $exclude, 
                    ),
                ),
            ),
        ),
        'image' => array(
            'callback' => 'SUPER_Shortcodes::image',
            'name' => __( 'Image', 'super' ),
            'icon' => 'picture-o',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'image' => array(
                            'name'=>'Image',
                            'default'=> (!isset($attributes['image']) ? '' : $attributes['image']),
                            'type'=>'image',
                        ),
                        'width' => array(
                            'type' => 'slider', 
                            'default'=> (!isset($attributes['width']) ? 150 : $attributes['width']),
                            'min' => 0, 
                            'max' => 600, 
                            'steps' => 10, 
                            'name' => __( 'Maximum image width in pixels', 'super' ), 
                            'desc' => __( 'Set to 0 to use default CSS width.', 'super' )
                        ),
                        'height' => array(
                            'type' => 'slider', 
                            'default'=> (!isset($attributes['height']) ? 250 : $attributes['height']),
                            'min' => 0, 
                            'max' => 600, 
                            'steps' => 10, 
                            'name' => __( 'Maximum image height in pixels', 'super' ), 
                            'desc' => __( 'Set to 0 to use default CSS width.', 'super' )
                        ),
                        'alignment' => array(
                            'name'=>'Image Alignment',
                            'desc'=>'Choose how to align your image',
                            'default'=> (!isset($attributes['alignment']) ? 'left' : $attributes['alignment']),
                            'type'=>'select',
                            'values'=>array(
                                'center'=>'Center',
                                'left'=>'Left',
                                'right'=>'Right',
                                ''=>'No alignment',
                            )
                        ),
                        'link' => array(
                            'name'=>'Image Link',
                            'desc'=>'Where should your image link to?',
                            'default'=> (!isset($attributes['link']) ? '' : $attributes['link']),
                            'filter'=>true,
                            'type'=>'select',
                            'values'=>array(
                                ''=>'No Link',
                                'custom'=>'Custom URL',
                                'post'=>'Post',
                                'page'=>'Page',
                            )
                        ),
                        'custom_link' => array(
                            'name'=>'Enter a custom URL to link to',
                            'default'=> (!isset($attributes['custom_link']) ? '' : $attributes['custom_link']),
                            'parent'=>'link',
                            'filter_value'=>'custom',    
                        ),
                        'post' => array(
                            'name'=>'Select a post to link to',
                            'default'=> (!isset($attributes['post']) ? '' : $attributes['post']),
                            'type'=>'select',
                            'values'=>SUPER_Common::list_posts_by_type_array('post'),
                            'parent'=>'link',
                            'filter_value'=>'post',    
                        ),
                        'page' => array(
                            'name'=>'Select a page to link to',
                            'default'=> (!isset($attributes['page']) ? '' : $attributes['page']),
                            'type'=>'select',
                            'values'=>SUPER_Common::list_posts_by_type_array('page'),
                            'parent'=>'link',
                            'filter_value'=>'page',    
                        ),
                        'target' => array(
                            'name'=>'Open new tab/window',
                            'default'=> (!isset($attributes['target']) ? '' : $attributes['target']),
                            'type'=>'select',
                            'values'=>array(
                                ''=>'Open in same window',
                                '_blank'=>'Open in new window',
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
            'name' => __( 'HTML', 'super' ),
            'icon' => 'file-code-o',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'title' => array(
                            'name'=>__( 'Title', 'super' ),
                            'desc'=>'('.__( 'optional', 'super' ).')',
                            'default'=> (!isset($attributes['title']) ? '' : $attributes['title']),
                        ),
                        'subtitle' => array(
                            'name'=>__( 'Sub Title', 'super' ),
                            'desc'=>'('.__( 'optional', 'super' ).')',
                            'default'=> (!isset($attributes['subtitle']) ? '' : $attributes['subtitle']),
                        ),
                        'html' => array(
                            'name'=>__( 'HTML', 'super' ),
                            'type'=>'textarea',
                            'default'=> (!isset($attributes['html']) ? 'Your HTML here...' : $attributes['html']),
                        ),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'recaptcha' => array(
            'callback' => 'SUPER_Shortcodes::recaptcha',
            'name' => __( 'reCAPTCHA', 'super' ),
            'icon' => 'shield',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'label' => $label, 
                        'description'=>$description,
                        'tooltip' => $tooltip,
                        'error' => $error,
                    ),
                ),
            ),
        ),
        'divider' => array(
            'callback' => 'SUPER_Shortcodes::divider',
            'name' => 'Divider',
            'icon' => 'minus',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'color' => array(
                            'name'=>__( 'Divider color', 'super' ),
                            'desc'=>__( 'Choose a custom border color.', 'super' ),
                            'default'=> (!isset($attributes['color']) ? '#444444' : $attributes['color']),
                            'type'=>'color',
                        ),
                        'border' => array(
                            'name'=>__( 'Border style', 'super' ),
                            'default'=> (!isset($attributes['border']) ? 'single' : $attributes['border']),
                            'type'=>'select', 
                            'filter'=>true,
                            'values'=>array(
                                'single' => __( 'Single', 'super' ),
                                'double' => __( 'Double', 'super' ),
                            ),
                        ),
                        'thickness' => array(
                            'name'=>__( 'Border thickness', 'super' ),
                            'default'=> (!isset($attributes['thickness']) ? 1 : $attributes['thickness']),
                            'type'=>'slider',
                            'min'=>1,
                            'max'=>20,
                            'steps'=>1,
                        ),
                        'height' => array(
                            'name'=>__( 'Divider height', 'super' ),
                            'default'=> (!isset($attributes['height']) ? 1 : $attributes['height']),
                            'type'=>'slider',
                            'min'=>1,
                            'max'=>20,
                            'steps'=>1,
                        ),
                        'border_style' => array(
                            'name'=>__( 'Border style', 'super' ),
                            'default'=> (!isset($attributes['border_style']) ? 'dashed' : $attributes['border_style']),
                            'type'=>'select', 
                            'values'=>array(
                                'solid' => __( 'Solid', 'super' ),
                                'dotted' => __( 'Dotted', 'super' ),
                                'dashed' => __( 'Dashed', 'super' ),
                            ),
                        ),
                        'width' => array(
                            'name'=>__( 'Divider weight', 'super' ),
                            'desc'=>__( 'Define the width for the divider.', 'super' ),
                            'default'=> (!isset($attributes['width']) ? '100' : $attributes['width']),
                            'type'=>'select', 
                            'filter'=>true,
                            'values'=>array(
                                '100' => '100% '.__( 'width', 'super' ),
                                '75' => '75% '.__( 'width', 'super' ),
                                '50' => '50% '.__( 'width', 'super' ),
                                '25' => '25% '.__( 'width', 'super' ),
                                'custom' => __( 'Custom width in pixels', 'super' ),
                            )
                        ),
                        'custom_width' => array(
                            'name'=>__( 'Divider custom width', 'super' ),
                            'desc'=>__( 'Define a custom width for the divider. Use a pixel value. eg: 150px', 'super' ),
                            'default'=> (!isset($attributes['custom_width']) ? '150px' : $attributes['custom_width']),
                            'type'=>'text', 
                            'parent'=>'width',
                            'filter_value'=>'custom',
                        ),
                        'align' => array(
                            'name'=>__( 'Divider alignment', 'super' ),
                            'default'=> (!isset($attributes['align']) ? 'left' : $attributes['align']),
                            'type'=>'select', 
                            'values'=>array(
                                'left' => __( 'Align Left', 'super' ),
                                'center' => __( 'Align Center', 'super' ),
                                'right' => __( 'Align Right', 'super' ),
                            ),
                        ),
                        'back' => array(
                            'name'=>__( 'Back to top button', 'super' ),
                            'default'=> (!isset($attributes['back']) ? '0' : $attributes['back']),
                            'type'=>'select', 
                            'values'=>array(
                                '0' => __( 'Hide back to top button', 'super' ),
                                '1' => __( 'Show back to top button', 'super' ),
                            ),
                        ),
                    ),
                ),
                'padding' => array(
                    'name' => __( 'Padding', 'super' ),
                    'fields' => array(
                        'padding_top' => array(
                            'name'=>__( 'Padding top', 'super' ),
                            'default'=> (!isset($attributes['padding_top']) ? 20 : $attributes['padding_top']),
                            'type'=>'slider',
                            'min'=>0,
                            'max'=>100,
                            'steps'=>5,
                        ),
                        'padding_bottom' => array(
                            'name'=>__( 'Padding bottom', 'super' ),
                            'default'=> (!isset($attributes['padding_bottom']) ? 20 : $attributes['padding_bottom']),
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
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'height' => array(
                            'name'=>'Height in pixels', 
                            'default'=> (!isset($attributes['height']) ? 50 : $attributes['height']),
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
        //Maybe to be used for later version?
        /*
        'like' => array(
            'name' => 'Like',
            'icon' => 'thumbs-up',
            'atts' => array(),
        ),
        'whatsapp' => array(
            'name' => 'Whatsapp',
            'icon' => 'whatsapp',
            'atts' => array(),
        ),
        */
    )
);