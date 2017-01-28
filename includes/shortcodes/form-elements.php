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
                        'custom_regex' => $custom_regex,
                        'conditional_validation' => $conditional_validation,
                        'conditional_validation_value' => $conditional_validation_value,
                        'may_be_empty' => $may_be_empty,
                        'error' => $error,
                    ),
                ),
                'auto_suggest' => array(
                    'name' => __( 'Auto suggest', 'super-forms' ),
                    'fields' => array(
                        'enable_auto_suggest' => array(
                            'desc' => __( 'Wether or not to use the auto suggest feature', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['enable_auto_suggest'] ) ? '' : $attributes['enable_auto_suggest'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Enable auto suggest', 'super-forms' ),
                            )
                        ),
                        'retrieve_method' => array(
                            'name' => __( 'Retrieve method', 'super-forms' ), 
                            'desc' => __( 'Select a method for retrieving items', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method'] ) ? 'custom' : $attributes['retrieve_method'] ),
                            'type' => 'select', 
                            'values' => array(
                                'custom' => __( 'Custom items', 'super-forms' ), 
                                'taxonomy' => __( 'Specific taxonomy (categories)', 'super-forms' ),
                                'post_type' => __( 'Specific posts (post_type)', 'super-forms' ),
                                'csv' => __( 'CSV file', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'enable_auto_suggest',
                            'filter_value'=>'true'
                        ),
                        'retrieve_method_csv' => array(
                            'name' => __( 'Upload CSV file', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_csv'] ) ? '' : $attributes['retrieve_method_csv'] ),
                            'type' => 'file',
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'csv'
                        ),
                        'retrieve_method_delimiter' => array(
                            'name' => __( 'Custom delimiter', 'super-forms' ), 
                            'desc' => __( 'Set a custom delimiter to seperate the values on each row' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_delimiter'] ) ? ',' : $attributes['retrieve_method_delimiter'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'csv'
                        ),
                        'retrieve_method_enclosure' => array(
                            'name' => __( 'Custom enclosure', 'super-forms' ), 
                            'desc' => __( 'Set a custom enclosure character for values' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_enclosure'] ) ? '"' : $attributes['retrieve_method_enclosure'] ),
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
                        'retrieve_method_post' => array(
                            'name' => __( 'Post type (e.g page, post or product)', 'super-forms' ), 
                            'desc' => __( 'Enter the name of the post type', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_post'] ) ? 'post' : $attributes['retrieve_method_post'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'post_type'
                        ),
                        'retrieve_method_exclude_taxonomy' => array(
                            'name' => __( 'Exclude a category', 'super-forms' ), 
                            'desc' => __( 'Enter the category ID\'s to exclude seperated by comma\'s', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_exclude_taxonomy'] ) ? '' : $attributes['retrieve_method_exclude_taxonomy'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'taxonomy'
                        ),
                        'retrieve_method_exclude_post' => array(
                            'name' => __( 'Exclude a post', 'super-forms' ), 
                            'desc' => __( 'Enter the post ID\'s to exclude seperated by comma\'s', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_exclude_post'] ) ? '' : $attributes['retrieve_method_exclude_post'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'post_type'
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
                            'filter_value'=>'taxonomy,post_type'
                        ),
                        'retrieve_method_value' => array(
                            'name' => __( 'Retrieve Slug, ID or Title as value', 'super-forms' ), 
                            'desc' => __( 'Select if you want to retrieve slug, ID or the title as value', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_value'] ) ? 'slug' : $attributes['retrieve_method_value'] ),
                            'type' => 'select', 
                            'values' => array(
                                'slug' => __( 'Slug (default)', 'super-forms' ), 
                                'id' => __( 'ID', 'super-forms' ),
                                'title' => __( 'Title', 'super-forms' )
                            ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'taxonomy,post_type'
                        ),

                        'autosuggest_items' => array(
                            'type' => 'radio_items',
                            'default'=> ( !isset( $attributes['autosuggest_items'] ) ? 
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
                                ) : $attributes['autosuggest_items']
                            ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'custom'
                        ),
                    )
                ),

                // @since 2.2.0
                'enable_search' => array(
                    'name' => __( 'Contact entry search (populate form with data)', 'super-forms' ),
                    'fields' => array(
                        'enable_search' => array(
                            'label' => __( 'By default it will search for contact entries based on their title.<br />A filter hook can be used to retrieve different data.', 'super-forms' ), 
                            'desc' => __( 'Wether or not to use the contact entry search feature', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['enable_search'] ) ? '' : $attributes['enable_search'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Enable contact entry search by title', 'super-forms' ),
                            )
                        ),
                        'search_method' => array(
                            'name' => __( 'Search method', 'super-forms' ), 
                            'desc' => __( 'Select how you want to filter entries', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['search_method'] ) ? 'equals' : $attributes['search_method'] ),
                            'type' => 'select', 
                            'values' => array(
                                'equals' => __( '== Equal (default)', 'super-forms' ),
                                'contains' => __( '?? Contains', 'super-forms' ), 
                            ),
                            'filter'=>true,
                            'parent'=>'enable_search',
                            'filter_value'=>'true'
                        ),
                    )
                ),

                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'disabled' => $disabled,
                        'grouped' => $grouped,
                        'mask' => array(
                            'default'=> ( !isset( $attributes['mask'] ) ? '' : $attributes['mask'] ),
                            'name' => __( 'Enter a predefined mask e.g: (999) 999-9999', 'super-forms' ), 
                            'label' => __( '(leave blank for no input mask)<br />a - Represents an alpha character (A-Z,a-z)<br />9 - Represents a numeric character (0-9)<br />* - Represents an alphanumeric character (A-Z,a-z,0-9)', 'super-forms' ),
                        ),
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'maxnumber' => $maxnumber,
                        'minnumber' => $minnumber,
                        'width' => $width,
                        'wrapper_width' => $wrapper_width,
                        'exclude' => $exclude,
                        'error_position' => $error_position,

                        // @since 1.9
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,

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
                        
                        // @since 1.9
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,

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
                        'drag_drop_upload' => array(
                            'name' => __( 'Enable Drag & Drop Upload Support', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['drag_drop_upload'] ) ? 'false' : $attributes['drag_drop_upload'] ),
                            'type' => 'select', 
                            'values' => array(
                                'false' => __( 'No (disabled)', 'super-forms' ), 
                                'true' => __( 'Yes (enabled)', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'media_buttons',
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
                        'force_br' => array(
                            'name' => __( 'Force to use line breaks instead of paragraphs', 'super-forms' ), 
                            'desc' => __( 'Let a new line break act as shift+enter', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['force_br'] ) ? 'false' : $attributes['force_br'] ),
                            'type' => 'select', 
                            'values' => array(
                                'false' => __( 'No (disabled)', 'super-forms' ),
                                'true' => __( 'Yes (enabled)', 'super-forms' ), 
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
                                'post_type' => __( 'Specific posts (post_type)', 'super-forms' ),
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
                        'retrieve_method_delimiter' => array(
                            'name' => __( 'Custom delimiter', 'super-forms' ), 
                            'desc' => __( 'Set a custom delimiter to seperate the values on each row' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_delimiter'] ) ? ',' : $attributes['retrieve_method_delimiter'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'csv'
                        ),
                        'retrieve_method_enclosure' => array(
                            'name' => __( 'Custom enclosure', 'super-forms' ), 
                            'desc' => __( 'Set a custom enclosure character for values' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_enclosure'] ) ? '"' : $attributes['retrieve_method_enclosure'] ),
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
                        'retrieve_method_post' => array(
                            'name' => __( 'Taxonomy slug', 'super-forms' ), 
                            'desc' => __( 'Enter the taxonomy slug name e.g category or product_cat', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_post'] ) ? 'post' : $attributes['retrieve_method_post'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'post_type'
                        ),
                        'retrieve_method_exclude_taxonomy' => array(
                            'name' => __( 'Exclude a category', 'super-forms' ), 
                            'desc' => __( 'Enter the category ID\'s to exclude seperated by comma\'s', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_exclude_taxonomy'] ) ? '' : $attributes['retrieve_method_exclude_taxonomy'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'taxonomy'
                        ),
                        'retrieve_method_exclude_post' => array(
                            'name' => __( 'Exclude a post', 'super-forms' ), 
                            'desc' => __( 'Enter the post ID\'s to exclude seperated by comma\'s', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_exclude_post'] ) ? '' : $attributes['retrieve_method_exclude_post'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'post_type'
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
                            'filter_value'=>'taxonomy,post_type'
                        ),
                        'retrieve_method_value' => array(
                            'name' => __( 'Retrieve Slug, ID or Title as value', 'super-forms' ), 
                            'desc' => __( 'Select if you want to retrieve slug, ID or the title as value', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_value'] ) ? 'slug' : $attributes['retrieve_method_value'] ),
                            'type' => 'select', 
                            'values' => array(
                                'slug' => __( 'Slug (default)', 'super-forms' ), 
                                'id' => __( 'ID', 'super-forms' ),
                                'title' => __( 'Title', 'super-forms' )
                            ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'taxonomy,post_type'
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

                        // @since 1.2.7
                        'admin_email_value' => $admin_email_value,
                        'confirm_email_value' => $confirm_email_value,
                        
                        // @since 1.2.9
                        'contact_entry_value' => $contact_entry_value,

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
                        'error_position' => $error_position_left_only,
                    
                        // @since 1.9
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,
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
                        'retrieve_method' => array(
                            'name' => __( 'Retrieve method', 'super-forms' ), 
                            'desc' => __( 'Select a method for retrieving items', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method'] ) ? 'custom' : $attributes['retrieve_method'] ),
                            'type' => 'select', 
                            'filter'=>true,
                            'values' => array(
                                'custom' => __( 'Custom items', 'super-forms' ), 
                                'taxonomy' => __( 'Specific taxonomy (categories)', 'super-forms' ),
                                'post_type' => __( 'Specific posts (post_type)', 'super-forms' ),
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
                        'retrieve_method_delimiter' => array(
                            'name' => __( 'Custom delimiter', 'super-forms' ), 
                            'desc' => __( 'Set a custom delimiter to seperate the values on each row' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_delimiter'] ) ? ',' : $attributes['retrieve_method_delimiter'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'csv'
                        ),
                        'retrieve_method_enclosure' => array(
                            'name' => __( 'Custom enclosure', 'super-forms' ), 
                            'desc' => __( 'Set a custom enclosure character for values' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_enclosure'] ) ? '"' : $attributes['retrieve_method_enclosure'] ),
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
                        'retrieve_method_post' => array(
                            'name' => __( 'Taxonomy slug', 'super-forms' ), 
                            'desc' => __( 'Enter the taxonomy slug name e.g category or product_cat', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_post'] ) ? 'post' : $attributes['retrieve_method_post'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'post_type'
                        ),
                        'retrieve_method_exclude_taxonomy' => array(
                            'name' => __( 'Exclude a category', 'super-forms' ), 
                            'desc' => __( 'Enter the category ID\'s to exclude seperated by comma\'s', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_exclude_taxonomy'] ) ? '' : $attributes['retrieve_method_exclude_taxonomy'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'taxonomy'
                        ),
                        'retrieve_method_exclude_post' => array(
                            'name' => __( 'Exclude a post', 'super-forms' ), 
                            'desc' => __( 'Enter the post ID\'s to exclude seperated by comma\'s', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_exclude_post'] ) ? '' : $attributes['retrieve_method_exclude_post'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'post_type'
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
                            'filter_value'=>'taxonomy,post_type'
                        ),
                        'retrieve_method_value' => array(
                            'name' => __( 'Retrieve Slug, ID or Title as value', 'super-forms' ), 
                            'desc' => __( 'Select if you want to retrieve slug, ID or the title as value', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_value'] ) ? 'slug' : $attributes['retrieve_method_value'] ),
                            'type' => 'select', 
                            'values' => array(
                                'slug' => __( 'Slug (default)', 'super-forms' ), 
                                'id' => __( 'ID', 'super-forms' ),
                                'title' => __( 'Title', 'super-forms' )
                            ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'taxonomy,post_type'
                        ),
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
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'custom'                            
                        ),

                        // @since 1.2.7
                        'admin_email_value' => $admin_email_value,
                        'confirm_email_value' => $confirm_email_value,
                        
                        // @since 1.2.9
                        'contact_entry_value' => $contact_entry_value,

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
                        
                        // @since 1.9
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,

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
                        'retrieve_method' => array(
                            'name' => __( 'Retrieve method', 'super-forms' ), 
                            'desc' => __( 'Select a method for retrieving items', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method'] ) ? 'custom' : $attributes['retrieve_method'] ),
                            'type' => 'select', 
                            'filter'=>true,
                            'values' => array(
                                'custom' => __( 'Custom items', 'super-forms' ), 
                                'taxonomy' => __( 'Specific taxonomy (categories)', 'super-forms' ),
                                'post_type' => __( 'Specific posts (post_type)', 'super-forms' ),
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
                        'retrieve_method_delimiter' => array(
                            'name' => __( 'Custom delimiter', 'super-forms' ), 
                            'desc' => __( 'Set a custom delimiter to seperate the values on each row' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_delimiter'] ) ? ',' : $attributes['retrieve_method_delimiter'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'csv'
                        ),
                        'retrieve_method_enclosure' => array(
                            'name' => __( 'Custom enclosure', 'super-forms' ), 
                            'desc' => __( 'Set a custom enclosure character for values' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_enclosure'] ) ? '"' : $attributes['retrieve_method_enclosure'] ),
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
                        'retrieve_method_post' => array(
                            'name' => __( 'Taxonomy slug', 'super-forms' ), 
                            'desc' => __( 'Enter the taxonomy slug name e.g category or product_cat', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_post'] ) ? 'post' : $attributes['retrieve_method_post'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'post_type'
                        ),
                        'retrieve_method_exclude_taxonomy' => array(
                            'name' => __( 'Exclude a category', 'super-forms' ), 
                            'desc' => __( 'Enter the category ID\'s to exclude seperated by comma\'s', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_exclude_taxonomy'] ) ? '' : $attributes['retrieve_method_exclude_taxonomy'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'taxonomy'
                        ),
                        'retrieve_method_exclude_post' => array(
                            'name' => __( 'Exclude a post', 'super-forms' ), 
                            'desc' => __( 'Enter the post ID\'s to exclude seperated by comma\'s', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_exclude_post'] ) ? '' : $attributes['retrieve_method_exclude_post'] ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'post_type'
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
                            'filter_value'=>'taxonomy,post_type'
                        ),
                        'retrieve_method_value' => array(
                            'name' => __( 'Retrieve Slug, ID or Title as value', 'super-forms' ), 
                            'desc' => __( 'Select if you want to retrieve slug, ID or the title as value', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['retrieve_method_value'] ) ? 'slug' : $attributes['retrieve_method_value'] ),
                            'type' => 'select', 
                            'values' => array(
                                'slug' => __( 'Slug (default)', 'super-forms' ), 
                                'id' => __( 'ID', 'super-forms' ),
                                'title' => __( 'Title', 'super-forms' )
                            ),
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'taxonomy,post_type'
                        ),
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
                            'filter'=>true,
                            'parent'=>'retrieve_method',
                            'filter_value'=>'custom'
                        ),

                        // @since 1.2.7
                        'admin_email_value' => $admin_email_value,
                        'confirm_email_value' => $confirm_email_value,

                        // @since 1.2.9
                        'contact_entry_value' => $contact_entry_value,

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
                        
                        // @since 1.9
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,

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
        'quantity' => array(
            'callback' => 'SUPER_Shortcodes::quantity_field',
            'name' => __( 'Quantity field', 'super-forms' ),
            'icon' => 'plus-square',
            'atts' => array(
                 'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='quantity'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Quantity'),
                        'label' => $label,
                        'description'=>$description,                    
                        'value' => array(
                            'default'=> ( !isset( $attributes['value'] ) ? '0' : $attributes['value'] ),
                            'name' => __( 'Default value', 'super-forms' ), 
                            'desc' => __( 'Set a default value for this field (leave blank for none)', 'super-forms' )
                        ),
                        'tooltip' => $tooltip,
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
                            'name' => __( 'The amount to add or deduct when button is clicked', 'super-forms' ), 
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
                    
                        // @since 1.9
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,

                    ),
                ),
                'conditional_logic' => $conditional_logic_array
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
                            'name'=>__( 'Currency', 'super-forms' ), 
                            'desc'=>__( 'Set the currency of or leave empty for no currency e.g: $ or €', 'super-forms' ),
                            'default'=> ( !isset( $attributes['currency'] ) ? '$' : $attributes['currency'] ),
                            'placeholder'=>'$',
                        ),
                        'decimals' => array(
                            'name'=>__( 'Length of decimal', 'super-forms' ), 
                            'desc'=>__( 'Choose a length for your decimals (default = 2)', 'super-forms' ), 
                            'default'=> (!isset($attributes['decimals']) ? '2' : $attributes['decimals']),
                            'type'=>'select', 
                            'values'=>array(
                                '0' => __( '0 decimals', 'super-forms' ),
                                '1' => __( '1 decimal', 'super-forms' ),
                                '2' => __( '2 decimals', 'super-forms' ),
                                '3' => __( '3 decimals', 'super-forms' ),
                                '4' => __( '4 decimals', 'super-forms' ),
                                '5' => __( '5 decimals', 'super-forms' ),
                                '6' => __( '6 decimals', 'super-forms' ),
                                '7' => __( '7 decimals', 'super-forms' ),
                            )
                        ),
                        'decimal_separator' => array(
                            'name'=>__( 'Decimal separator', 'super-forms' ), 
                            'desc'=>__( 'Choose your decimal separator (comma or dot)', 'super-forms' ), 
                            'default'=> (!isset($attributes['decimal_separator']) ? '.' : $attributes['decimal_separator']),
                            'type'=>'select', 
                            'values'=>array(
                                '.' => __( '. (dot)', 'super-forms' ),
                                ',' => __( ', (comma)', 'super-forms' ), 
                            )
                        ),
                        'thousand_separator' => array(
                            'name'=>__( 'Thousand separator', 'super-forms' ), 
                            'desc'=>__( 'Choose your thousand separator (empty, comma or dot)', 'super-forms' ), 
                            'default'=> (!isset($attributes['thousand_separator']) ? ',' : $attributes['thousand_separator']),
                            'type'=>'select', 
                            'values'=>array(
                                '' => __( 'None (empty)', 'super-forms' ),
                                '.' => __( '. (dot)', 'super-forms' ),
                                ',' => __( ', (comma)', 'super-forms' ), 
                            )
                        ),
                        'tooltip' => $tooltip,
                        'validation' => $special_validations,
                        'custom_regex' => $custom_regex,
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

                        // @since 1.9
                        'wrapper_class' => $wrapper_class,

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

        // @since 2.1.0
        'currency' => array(
            'callback' => 'SUPER_Shortcodes::currency',
            'name' => __( 'Currency field', 'super-forms' ),
            'icon' => 'usd',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='amount'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Amount'),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder( $attributes, __( '$0.00', 'super-forms' ) ),
                        'value' => array(
                            'default'=> ( !isset( $attributes['value'] ) ? '' : $attributes['value'] ),
                            'name' => __( 'Default value', 'super-forms' ), 
                            'desc' => __( 'Set a default value for this field (leave blank for none)', 'super-forms' )
                        ),
                        'format' => array(
                            'default'=> ( !isset( $attributes['format'] ) ? '' : $attributes['format'] ),
                            'name' => __( 'Number format (example: GB / Gygabyte)', 'super-forms' ), 
                            'desc' => __( 'Set a number format e.g: Gygabyte, Kilometers etc. (leave blank for none)', 'super-forms' )
                        ),
                        'currency' => array(
                            'name'=>__( 'Currency', 'super-forms' ), 
                            'desc'=>__( 'Set the currency of or leave empty for no currency e.g: $ or €', 'super-forms' ),
                            'default'=> ( !isset( $attributes['currency'] ) ? '$' : $attributes['currency'] ),
                            'placeholder'=>'$',
                        ),
                        'decimals' => array(
                            'name'=>__( 'Length of decimal', 'super-forms' ), 
                            'desc'=>__( 'Choose a length for your decimals (default = 2)', 'super-forms' ), 
                            'default'=> (!isset($attributes['decimals']) ? '2' : $attributes['decimals']),
                            'type'=>'select', 
                            'values'=>array(
                                '0' => __( '0 decimals', 'super-forms' ),
                                '1' => __( '1 decimal', 'super-forms' ),
                                '2' => __( '2 decimals', 'super-forms' ),
                                '3' => __( '3 decimals', 'super-forms' ),
                                '4' => __( '4 decimals', 'super-forms' ),
                                '5' => __( '5 decimals', 'super-forms' ),
                                '6' => __( '6 decimals', 'super-forms' ),
                                '7' => __( '7 decimals', 'super-forms' ),
                            )
                        ),
                        'decimal_separator' => array(
                            'name'=>__( 'Decimal separator', 'super-forms' ), 
                            'desc'=>__( 'Choose your decimal separator (comma or dot)', 'super-forms' ), 
                            'default'=> (!isset($attributes['decimal_separator']) ? '.' : $attributes['decimal_separator']),
                            'type'=>'select', 
                            'values'=>array(
                                '.' => __( '. (dot)', 'super-forms' ),
                                ',' => __( ', (comma)', 'super-forms' ), 
                            )
                        ),
                        'thousand_separator' => array(
                            'name'=>__( 'Thousand separator', 'super-forms' ), 
                            'desc'=>__( 'Choose your thousand separator (empty, comma or dot)', 'super-forms' ), 
                            'default'=> (!isset($attributes['thousand_separator']) ? ',' : $attributes['thousand_separator']),
                            'type'=>'select', 
                            'values'=>array(
                                '' => __( 'None (empty)', 'super-forms' ),
                                '.' => __( '. (dot)', 'super-forms' ),
                                ',' => __( ', (comma)', 'super-forms' ), 
                            )
                        ),
                        'tooltip' => $tooltip,
                        'validation' => $special_validations,
                        'custom_regex' => $custom_regex,
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

                        // @since 1.9
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,

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
                        
                        'enable_image_button' => array(
                            'desc' => __( 'Wether or not to use an image button', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['enable_image_button'] ) ? '' : $attributes['enable_image_button'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Use image button instead of text button', 'super-forms' ),
                            )
                        ),
                        'image' => array(
                            'name'=>__( 'Image Button (leave blank to use text button)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['image']) ? '' : $attributes['image']),
                            'type'=>'image',
                            'filter'=>true,
                            'parent'=>'enable_image_button',
                            'filter_value'=>'true'

                        ),
                        'max_img_width' => array(
                            'name'=>__( 'Max image width in pixels (0 = no max)', 'super-forms' ),
                            'desc'=>__( '0 = no max width', 'super-forms' ),
                            'default'=> ( !isset( $attributes['max_img_width']) ? 200 : $attributes['max_img_width']),
                            'type'=>'slider',
                            'min'=>1,
                            'max'=>500,
                            'steps'=>1,
                            'filter'=>true,
                            'parent'=>'enable_image_button',
                            'filter_value'=>'true'
                        ),
                        'max_img_height' => array(
                            'name'=>__( 'Max image height in pixels (0 = no max)', 'super-forms' ),
                            'desc'=>__( '0 = no max height', 'super-forms' ),
                            'default'=> ( !isset( $attributes['max_img_height']) ? 300 : $attributes['max_img_height']),
                            'type'=>'slider',
                            'min'=>1,
                            'max'=>500,
                            'steps'=>1,
                            'filter'=>true,
                            'parent'=>'enable_image_button',
                            'filter_value'=>'true'
                        ),
                        
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

                        // @since 1.9
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,

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
                            'desc' => __( 'Set a default value for this field (leave blank for none)', 'super-forms' ),
                        ),
                        'current_date' => array(
                            'default'=> ( !isset( $attributes['current_date'] ) ? '' : $attributes['current_date'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Return the current date as default value', 'super-forms' ),
                            )
                        ),
                        'work_days' => array(
                            'default'=> ( !isset( $attributes['work_days'] ) ? 'true' : $attributes['work_days'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Allow users to select work days', 'super-forms' ),
                            )
                        ),
                        'weekends' => array(
                            'default'=> ( !isset( $attributes['weekends'] ) ? 'true' : $attributes['weekends'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Allow users to select weekends', 'super-forms' ),
                            )
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
                        'width' => $width,
                        'wrapper_width' => $wrapper_width,
                        'minlength' => array(
                            'name'=>__( 'Date range (minimum)', 'super-forms' ),
                            'desc'=>__( 'Amount in days to add or deduct based on current day<br />(leave blank to remove limitations)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['minlength']) ? '' : $attributes['minlength']),
                        ),
                        'connected_min' => array(
                            'name'=>__( 'Min. Connect with other datepicker', 'super-forms' ),
                            'desc'=>__( 'Achieve date range with 2 datepickers', 'super-forms' ),
                            'default'=> ( !isset( $attributes['connected_min']) ? '' : $attributes['connected_min']),
                            'type'=>'select',
                            'values'=>array(
                                '' => __( '- Not connected -', 'super-forms' ),
                            )
                        ),
                        'connected_min_days' => array(
                            'type' => 'slider', 
                            'default'=> ( !isset( $attributes['connected_min_days']) ? 1 : $attributes['connected_min_days']),
                            'min' => -100, 
                            'max' => 100, 
                            'steps' => 1, 
                            'name' => __( 'Days to add/deduct based on connected datepicker', 'super-forms' ), 
                        ),
                        'maxlength' => array(
                            'name'=>__( 'Date range (maximum)', 'super-forms' ),
                            'desc'=>__( 'Amount in days to add or deduct based on current day<br />(leave blank to remove limitations)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['maxlength']) ? '' : $attributes['maxlength']),
                        ),
                        'connected_max' => array(
                            'name'=>__( 'Max. Connect with other datepicker', 'super-forms' ),
                            'desc'=>__( 'Achieve date range with 2 datepickers', 'super-forms' ),
                            'default'=> ( !isset( $attributes['connected_max']) ? '' : $attributes['connected_max']),
                            'type'=>'select',
                            'values'=>array(
                                '' => __( '- Not connected -', 'super-forms' ),
                            )
                        ),
                        'connected_max_days' => array(
                            'type' => 'slider', 
                            'default'=> ( !isset( $attributes['connected_max_days']) ? 1 : $attributes['connected_max_days']),
                            'min' => -100, 
                            'max' => 100, 
                            'steps' => 1, 
                            'name' => __( 'Days to add/deduct based on connected datepicker', 'super-forms' ), 
                        ),

                        'exclude' => $exclude,
                        'error_position' => $error_position,

                        // @since 1.9
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,

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
                        'current_time' => array(
                            'default'=> ( !isset( $attributes['current_time'] ) ? '' : $attributes['current_time'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Return the current time as default value', 'super-forms' ),
                            )
                        ),
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
                        
                        // @since 1.9
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,
                        
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
                        'value' => array(
                            'name' => __( 'Default rating value (0 stars = default)', 'super-forms' ), 
                            'type' => 'slider', 
                            'default'=> ( !isset( $attributes['value'] ) ? 0 : $attributes['value'] ),
                            'min' => 0,
                            'max' => 5,
                            'steps' => 1,
                        ),
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

                        // @since 1.9
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,

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
                        'custom_regex' => $custom_regex,
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
                        
                        // @since 1.9
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,

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
                        'custom_regex' => $custom_regex,
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

                        // @since 1.9
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,            

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
                'random_code' => array(
                    'name' => __( 'Unique code generation', 'super-forms' ),
                    'fields' => array(
                        'enable_random_code' => array(
                            'default'=> ( !isset( $attributes['enable_random_code'] ) ? '' : $attributes['enable_random_code'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Enable code generation', 'super-forms' ),
                            )
                        ),
                        'code_length' => array(
                            'type' => 'slider', 
                            'default'=> ( !isset( $attributes['code_length']) ? 7 : $attributes['code_length']),
                            'min' => 5, 
                            'max' => 15, 
                            'steps' => 1, 
                            'name' => __( 'Code length', 'super-forms' ), 
                            'filter'=>true,
                            'parent'=>'enable_random_code',
                            'filter_value'=>'true'                            
                        ),
                        'code_characters' => array(
                            'name'=>__( 'Characters the code should contain', 'super-forms' ),
                            'default'=> ( !isset( $attributes['code_characters']) ? '1' : $attributes['code_characters']),
                            'type'=>'select',
                            'values'=>array(
                                '1'=>__( 'Numbers and Letters (default)', 'super-forms' ),
                                '2'=>__( 'Numbers, letters and symbols', 'super-forms' ),
                                '3'=>__( 'Numbers only', 'super-forms' ),
                                '4'=>__( 'Letters only', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'enable_random_code',
                            'filter_value'=>'true'    
                        ),
                        'code_uppercase' => array(
                            'default'=> ( !isset( $attributes['code_uppercase'] ) ? 'true' : $attributes['code_uppercase'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Allow uppercase letters', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'code_characters',
                            'filter_value'=>'1,2,4' 
                        ),
                        'code_lowercase' => array(
                            'default'=> ( !isset( $attributes['code_lowercase'] ) ? '' : $attributes['code_lowercase'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Allow lowercase letters', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'code_characters',
                            'filter_value'=>'1,2,4' 
                        ),

                        'code_prefix' => array(
                            'name'=>__( 'Code prefix', 'super-forms' ),
                            'default'=> ( !isset( $attributes['code_prefix']) ? '' : $attributes['code_prefix']),
                            'filter'=>true,
                            'parent'=>'enable_random_code',
                            'filter_value'=>'true'    
                        ),
                        'code_suffix' => array(
                            'name'=>__( 'Code suffix', 'super-forms' ),
                            'default'=> ( !isset( $attributes['code_suffix']) ? '' : $attributes['code_suffix']),
                            'filter'=>true,
                            'parent'=>'enable_random_code',
                            'filter_value'=>'true'                        
                        ),


                    )
                ),
                'conditional_variable' => $conditional_variable_array
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

                // @since 1.9
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,
                    ),
                ),

                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'heading' => array(
            'callback' => 'SUPER_Shortcodes::heading',
            'name' => __( 'Heading', 'super-forms' ),
            'icon' => 'header',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'title' => array(
                            'name'=>__( 'Title', 'super-forms' ),
                            'default'=> ( !isset( $attributes['title']) ? 'Title' : $attributes['title']),
                        ),
                        'desc' => array(
                            'name'=>__( 'Description', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc']) ? '' : $attributes['desc']),
                        ),
                        'size' => array(
                            'name'=>__( 'Heading size', 'super-forms' ),
                            'default'=> ( !isset( $attributes['title']) ? 'h1' : $attributes['title']),
                            'type'=>'select', 
                            'values'=>array(
                                'h1' => __( 'Heading 1', 'super-forms' ),
                                'h2' => __( 'Heading 2', 'super-forms' ),
                                'h3' => __( 'Heading 3', 'super-forms' ),
                                'h4' => __( 'Heading 4', 'super-forms' ),
                                'h5' => __( 'Heading 5', 'super-forms' ),
                                'h6' => __( 'Heading 6', 'super-forms' ),
                            ),       
                        ),
                    ),
                ),
                'heading_styles' => array(
                    'name' => __( 'Heading Styles', 'super-forms' ),
                    'fields' => array(
                        'heading_color' => array(
                            'name'=>__( 'Font color', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_color']) ? '#444444' : $attributes['heading_color']),
                            'type'=>'color',
                        ),
                        'heading_size' => array(
                            'name'=>__( 'Font size in pixels (0 = default CSS size)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_size']) ? '0' : $attributes['heading_size']),
                            'type'=>'slider',
                            'min'=>0,
                            'max'=>200,
                            'steps'=>1,
                        ),
                        'heading_weight' => array(
                            'name'=>__( 'Font weight', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_weight']) ? '100' : $attributes['heading_weight']),
                            'type'=>'select', 
                            'values'=>array(
                                '100' => '100 (default)',
                                '200' => '200',
                                '300' => '300',
                                '400' => '400',
                                '500' => '500',
                                '600' => '600',
                                '700' => '700',
                                '800' => '800',
                                '900' => '900',
                            ),       
                        ),
                        'heading_align' => array(
                            'name'=>__( 'Text alignment', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_align']) ? 'left' : $attributes['heading_align']),
                            'type'=>'select', 
                            'values'=>array(
                                'left' => __( 'Left (default)' ,'super-forms' ),
                                'center' => __( 'Center' ,'super-forms' ),
                                'right' => __( 'Right' ,'super-forms' ),
                            ),       
                        ),
                        'heading_line_height' => array(
                            'name'=>__( 'Line height in pixels (0 = normal)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_line_height']) ? '0' : $attributes['heading_line_height']),
                            'type'=>'slider',
                            'min'=>0,
                            'max'=>200,
                            'steps'=>1,
                        ),
                        'heading_margin' => array(
                            'name'=>__( 'Margins (top right bottom left)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_margin']) ? '0px 0px 0px 0px' : $attributes['heading_margin']),
                            'placeholder' => '0px 0px 0px 0px'
                        ),
                    ),
                ),
                'desc_styles' => array(
                    'name' => __( 'Description Styles', 'super-forms' ),
                    'fields' => array(
                        'desc_color' => array(
                            'name'=>__( 'Font color', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_color']) ? '#444444' : $attributes['desc_color']),
                            'type'=>'color',
                        ),
                        'desc_size' => array(
                            'name'=>__( 'Font size in pixels (0 = default CSS size)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_size']) ? '0' : $attributes['desc_size']),
                            'type'=>'slider',
                            'min'=>0,
                            'max'=>200,
                            'steps'=>1,
                        ),
                        'desc_weight' => array(
                            'name'=>__( 'Font weight', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_weight']) ? '100' : $attributes['desc_weight']),
                            'type'=>'select', 
                            'values'=>array(
                                '100' => '100 (default)',
                                '200' => '200',
                                '300' => '300',
                                '400' => '400',
                                '500' => '500',
                                '600' => '600',
                                '700' => '700',
                                '800' => '800',
                                '900' => '900',
                            ),       
                        ),
                        'desc_align' => array(
                            'name'=>__( 'Text alignment', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_align']) ? 'left' : $attributes['desc_align']),
                            'type'=>'select', 
                            'values'=>array(
                                'left' => __( 'Left (default)' ,'super-forms' ),
                                'center' => __( 'Center' ,'super-forms' ),
                                'right' => __( 'Right' ,'super-forms' ),
                            ),       
                        ),
                        'desc_line_height' => array(
                            'name'=>__( 'Line height in pixels (0 = normal)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_line_height']) ? '0' : $attributes['desc_line_height']),
                            'type'=>'slider',
                            'min'=>0,
                            'max'=>200,
                            'steps'=>1,
                        ),
                        'desc_margin' => array(
                            'name'=>__( 'Margins (top right bottom left)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_margin']) ? '0px 0px 0px 0px' : $attributes['desc_margin']),
                            'placeholder' => '0px 0px 0px 0px'
                        ),
                    ),
                ),

                // @since 1.9
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,
                    ),
                ),

                'conditional_logic' => $conditional_logic_array
            )
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
                        'html' => array(
                            'name'=>__( 'HTML', 'super-forms' ),
                            'type'=>'textarea',
                            'default'=> ( !isset( $attributes['html']) ? 'Your HTML here...' : $attributes['html']),
                        ),

                    ),
                ),

                // @since 1.9
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,
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

                // @since 1.9
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,
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

                // @since 1.9
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,
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

                        // @since 2.0.0
                        'action' => array(
                            'name'=>__( 'Button action / method', 'super-forms' ),
                            'desc'=>__( 'What should this button do?', 'super-forms' ),
                            'default'=> ( !isset( $attributes['action']) ? 'submit' : $attributes['action']),
                            'type'=>'select',
                            'values'=>array(
                                'submit'=>__( 'Submit the form (default)', 'super-forms' ),
                                'clear'=>__( 'Clear / Reset the form', 'super-forms' ),
                                'url'=>__( 'Redirect to link or URL', 'super-forms' ),
                            ),
                            'filter'=>true,
                        ),

                        'name' => array(
                            'name'=>__( 'Button name', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['name'] ) ? __( 'Submit', 'super-forms' ) : $attributes['name'] ),
                            'parent'=>'action',
                            'filter_value'=>'submit,clear',
                            'filter'=>true,

                        ),

                        // @since 2.0.0
                        'loading' => array(
                            'name' => __('Button loading name', 'super-forms' ),
                            'default'=> ( !isset( $attributes['loading'] ) ? __( 'Loading...', 'super-forms' ) : $attributes['loading'] ),
                            'parent'=>'action',
                            'filter_value'=>'submit',
                            'filter'=>true,
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
                            'parent'=>'action',
                            'filter_value'=>'url',
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

                        // @since 1.9
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,

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
                            'parent'=>'custom_icon',
                            'filter_value'=>'custom',
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
                            'parent'=>'icon_visibility',
                            'filter_value'=>'hidden',
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