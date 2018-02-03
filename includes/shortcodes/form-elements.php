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
                    'data' => array(
                        'name' => __( 'email', 'super-forms' ),
                        'email' => __( 'Email address:', 'super-forms' ),
                        'placeholder' => __( 'Your Email Address', 'super-forms' ),
                        'validation' => 'email',
                        'icon' => 'envelope-o',
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
                        'icon' => 'toggle-down',
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
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => array(
                        array(
                            'tag' => 'text',
                            'group' => 'form_elements',
                            'data' => array(
                                'name' => 'first_name',
                                'email' => 'First name:',
                                'placeholder' => 'Your First Name',
                                'validation' => 'empty',
                            )
                        )
                    ),
                    'data' => array(
                        'size' => '1/2',                      
                    )
                ),
                array(
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => array(
                        array(
                            'tag' => 'text',
                            'group' => 'form_elements',
                            'data' => array(
                                'name' => 'last_name',
                                'email' => 'Last name:',
                                'placeholder' => 'Your Last Name',
                                'validation' => 'empty',
                            )
                        )
                    ),
                    'data' => array(
                        'size' => '1/2',
                    )
                ),
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
                    'data' => array(
                        'name' => __( 'address', 'super-forms' ),
                        'email' => __( 'Address:', 'super-forms' ),
                        'placeholder' => __( 'Your Address', 'super-forms' ),
                        'validation' => 'empty',
                        'icon_position' => 'outside',
                        'icon_align' => 'left',
                        'icon' => 'map-marker',
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
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => array(
                        array(
                            'tag' => 'text',
                            'group' => 'form_elements',
                            'data' => array(
                                'name' => 'zipcode',
                                'email' => 'Zipcode:',
                                'placeholder' => 'Zipcode',
                                'validation' => 'empty',
                                'minlength' => '4',
                                'icon' => 'map-marker',
                            )
                        )
                    ),
                    'data' => array(
                        'size' => '1/3',                      
                    )
                ),
                array(
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => array(
                        array(
                            'tag' => 'text',
                            'group' => 'form_elements',
                            'data' => array(
                                'name' => 'city',
                                'email' => 'City:',
                                'placeholder' => 'City',
                                'validation' => 'empty',
                                'icon' => 'map-marker',
                            )
                        )
                    ),
                    'data' => array(
                        'size' => '1/3',                      
                    )
                ),
                array(
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => array(
                        array(
                            'tag' => 'countries',
                            'group' => 'form_elements',
                            'data' => array(
                                'name' => 'country',
                                'email' => 'Country:',
                                'placeholder' => '- select your country -',
                                'validation' => 'empty',
                                'icon' => 'globe',
                            )
                        )
                    ),
                    'data' => array(
                        'size' => '1/3',                      
                    )
                ),
            ),
            'atts' => array(),
        ),

        'text_predefined' => array(
            'name' => __( 'Text field', 'super-forms' ),
            'icon' => 'list',
            'predefined' => array(
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'name', 'super-forms' ),
                        'email' => __( 'Name:', 'super-forms' ),
                        'placeholder' => __( 'Your Full Name', 'super-forms' ),
                        'icon' => 'user',
                    )
                )
            ),
            'atts' => array(),
        ),
        'text' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::text',
            'name' => __( 'Text field', 'super-forms' ),
            'icon' => 'list',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name( $attributes, '' ),
                        'email' => SUPER_Shortcodes::email( $attributes, '' ),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder( $attributes, '' ),
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
                        'conditional_validation_value2' => $conditional_validation_value2, // @since 3.6.0
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
                                'tags' => __( 'Tags', 'super-forms' ),
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
                            'filter_value'=>'csv',
                            'file_type'=>'text/csv'
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
                            'filter_value'=>'taxonomy,post_type,tags'
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

                // @since 3.1.0 - google distance calculation between 2 addresses
                // Example GET request: http://maps.googleapis.com/maps/api/directions/json?gl=uk&units=imperial&origin=Ulft&destination=7064BW
                'distance_calculator' => array(
                    'name' => __( 'Distance / Duration calculation (google directions)', 'super-forms' ),
                    'fields' => array(
                        'enable_distance_calculator' => array(
                            'desc' => __( 'Wether or not to use the distance calculator feature', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['enable_distance_calculator'] ) ? '' : $attributes['enable_distance_calculator'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Enable distance calculator', 'super-forms' ),
                            )
                        ),
                        'distance_method' => array(
                            'name' => __( 'Select if this field must act as Start or Destination', 'super-forms' ), 
                            'desc' => __( 'This option is required so that Super Forms knows how to calculate the distance', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['distance_method'] ) ? 'start' : $attributes['distance_method'] ),
                            'type' => 'select', 
                            'values' => array(
                                'start' => __( 'Start address', 'super-forms' ), 
                                'destination' => __( 'Destination address', 'super-forms' )
                            ),
                            'filter'=>true,
                            'parent'=>'enable_distance_calculator',
                            'filter_value'=>'true'
                        ),
                        'distance_start' => array(
                            'name' => __( 'Starting address (required)', 'super-forms' ), 
                            'label' => __( 'Enter a fixed address/zipcode or enter the unique field name to retrieve dynamic address from users', 'super-forms' ),
                            'desc' => __( 'Required to calculate distance between 2 locations', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['start'] ) ? '' : $attributes['start'] ),
                            'filter'=>true,
                            'parent'=>'distance_method',
                            'filter_value'=>'destination'
                        ),
                        'distance_destination' => array(
                            'name' => __( 'Destination address (required)', 'super-forms' ), 
                            'label' => __( 'Enter a fixed address/zipcode or enter the unique field name to retrieve dynamic address from users', 'super-forms' ),
                            'desc' => __( 'Required to calculate distance between 2 locations', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['destination'] ) ? '' : $attributes['destination'] ),
                            'filter'=>true,
                            'parent'=>'distance_method',
                            'filter_value'=>'start'
                        ),
                        'distance_value' => array(
                            'name' => __( 'Select what value to return (distance or duration)', 'super-forms' ), 
                            'desc' => __( 'After calculating the distance either the amount of meters or seconds can be returned', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['distance_value'] ) ? 'distance' : $attributes['distance_value'] ),
                            'type' => 'select', 
                            'values' => array(
                                'distance' => __( 'Distance in meters', 'super-forms' ), 
                                'duration' => __( 'Duration in seconds', 'super-forms' ),
                                'dis_text' => __( 'Distance text in km or miles', 'super-forms' ), 
                                'dur_text' => __( 'Duration text in minutes', 'super-forms' )
                            ),
                            'filter'=>true,
                            'parent'=>'distance_method',
                            'filter_value'=>'start'
                        ),
                        'distance_units' => array(
                            'name' => __( 'Select a unit system', 'super-forms' ), 
                            'desc' => __( 'This will determine if the textual distance is returned in meters or miles', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['distance_units'] ) ? 'metric' : $attributes['distance_units'] ),
                            'type' => 'select', 
                            'values' => array(
                                'metric' => __( 'Metric (distance returned in kilometers and meters)', 'super-forms' ), 
                                'imperial' => __( 'Imperial (distance returned in miles and feet)', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'distance_value',
                            'filter_value'=>'dis_text'
                        ),
                        'distance_field' => array(
                            'name' => __( 'Enter the unique field name which the distance value should be populated to (required)', 'super-forms' ), 
                            'label' => __( 'This can be a Text field or Hidden field (do not add brackets before and after).', 'super-forms' ),
                            'desc' => __( 'After doing the calculation the value will be populated to this field', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['distance_field'] ) ? '' : $attributes['distance_field'] ),
                            'filter'=>true,
                            'parent'=>'distance_method',
                            'filter_value'=>'start'
                        ),
                    )
                ),

                // @since 3.0.0 - google placed auto complete
                'address_auto_complete' => array(
                    'name' => __( 'Address auto complete (google places)', 'super-forms' ),
                    'fields' => array(
                        'enable_address_auto_complete' => array(
                            'desc' => __( 'Wether or not to use the address auto complete feature', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['enable_address_auto_complete'] ) ? '' : $attributes['enable_address_auto_complete'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Enable address auto complete', 'super-forms' ),
                            )
                        ),
                        'address_api_key' => array(
                            'name' => __( 'Google API key', 'super-forms' ), 
                            'label' => __( 'In order to make calls you have to enable these libraries in your <a target="_blank" href="https://console.developers.google.com">API manager</a>:<br />- Google Maps JavaScript API<br />- Google Places API Web Service', 'super-forms' ),
                            'desc' => __( 'Required to do API calls to retrieve data', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['address_api_key'] ) ? '' : $attributes['address_api_key'] ),
                            'filter'=>true,
                            'parent'=>'enable_address_auto_complete',
                            'filter_value'=>'true',
                            'required'=>true,
                        ),
                        'enable_address_auto_populate' => array(
                            'desc' => __( 'Auto populate address fields', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['enable_address_auto_populate'] ) ? '' : $attributes['enable_address_auto_populate'] ),
                            'type' => 'checkbox', 
                            'values' => array(
                                'true' => __( 'Enable address auto populate', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'enable_address_auto_complete',
                            'filter_value'=>'true'
                        ),
                        'address_auto_populate_mappings' => array( 
                            'name' => __( 'Map data with fields', 'super-forms' ), 
                            'desc' => __( 'The fields that should be populated with the address data.', 'super-forms' ),
                            'type' => 'address_auto_populate',
                            'default' => (!isset($attributes['address_auto_populate_mappings']) ? '' : $attributes['address_auto_populate_mappings']),
                            'filter' => true,
                            'parent' => 'enable_address_auto_populate',
                            'filter_value' => 'true'
                        ),
                    )
                ),
                // @since 2.9.0 - keyword input field
                'keyword_field' => array(
                    'name' => __( 'Enable keyword field', 'super-forms' ),
                    'fields' => array(
                        'enable_keywords' => array(
                            'desc' => __( 'Wether or not to enable keyword feature', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['enable_keywords'] ) ? '' : $attributes['enable_keywords'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Enable keyword user input', 'super-forms' ),
                            )
                        ),

                        // @since 3.7.0 - autosuggest keywords based on wordpress tags
                        'keywords_retrieve_method' => array(
                            'name' => __( 'Retrieve method', 'super-forms' ), 
                            'desc' => __( 'Select a method for retrieving items', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['keywords_retrieve_method'] ) ? 'free' : $attributes['keywords_retrieve_method'] ),
                            'type' => 'select', 
                            'values' => array(
                                'free' => __( 'Allow everything (no limitations)', 'super-forms' ),
                                'custom' => __( 'Custom items', 'super-forms' ),
                                'taxonomy' => __( 'Specific taxonomy (categories)', 'super-forms' ),
                                'post_type' => __( 'Specific posts (post_type)', 'super-forms' ),
                                'tags' => __( 'Tags (post_tag)', 'super-forms' ),
                                'csv' => __( 'CSV file', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'enable_keywords',
                            'filter_value'=>'true'
                        ),
                        'keywords_retrieve_method_csv' => array(
                            'name' => __( 'Upload CSV file', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['keywords_retrieve_method_csv'] ) ? '' : $attributes['keywords_retrieve_method_csv'] ),
                            'type' => 'file',
                            'filter'=>true,
                            'parent'=>'keywords_retrieve_method',
                            'filter_value'=>'csv',
                            'file_type'=>'text/csv'
                        ),
                        'keywords_retrieve_method_delimiter' => array(
                            'name' => __( 'Custom delimiter', 'super-forms' ), 
                            'desc' => __( 'Set a custom delimiter to seperate the values on each row' ), 
                            'default'=> ( !isset( $attributes['keywords_retrieve_method_delimiter'] ) ? ',' : $attributes['keywords_retrieve_method_delimiter'] ),
                            'filter'=>true,
                            'parent'=>'keywords_retrieve_method',
                            'filter_value'=>'csv'
                        ),
                        'keywords_retrieve_method_enclosure' => array(
                            'name' => __( 'Custom enclosure', 'super-forms' ), 
                            'desc' => __( 'Set a custom enclosure character for values' ), 
                            'default'=> ( !isset( $attributes['keywords_retrieve_method_enclosure'] ) ? '"' : $attributes['keywords_retrieve_method_enclosure'] ),
                            'filter'=>true,
                            'parent'=>'keywords_retrieve_method',
                            'filter_value'=>'csv'
                        ),                        
                        'keywords_retrieve_method_taxonomy' => array(
                            'name' => __( 'Taxonomy slug', 'super-forms' ), 
                            'desc' => __( 'Enter the taxonomy slug name e.g category or product_cat', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['keywords_retrieve_method_taxonomy'] ) ? 'category' : $attributes['keywords_retrieve_method_taxonomy'] ),
                            'filter'=>true,
                            'parent'=>'keywords_retrieve_method',
                            'filter_value'=>'taxonomy'
                        ),
                        'keywords_retrieve_method_post' => array(
                            'name' => __( 'Post type (e.g page, post or product)', 'super-forms' ), 
                            'desc' => __( 'Enter the name of the post type', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['keywords_retrieve_method_post'] ) ? 'post' : $attributes['keywords_retrieve_method_post'] ),
                            'filter'=>true,
                            'parent'=>'keywords_retrieve_method',
                            'filter_value'=>'post_type'
                        ),
                        'keywords_retrieve_method_exclude_taxonomy' => array(
                            'name' => __( 'Exclude a category', 'super-forms' ), 
                            'desc' => __( 'Enter the category ID\'s to exclude seperated by comma\'s', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['keywords_retrieve_method_exclude_taxonomy'] ) ? '' : $attributes['keywords_retrieve_method_exclude_taxonomy'] ),
                            'filter'=>true,
                            'parent'=>'keywords_retrieve_method',
                            'filter_value'=>'taxonomy'
                        ),
                        'keywords_retrieve_method_exclude_post' => array(
                            'name' => __( 'Exclude a post', 'super-forms' ), 
                            'desc' => __( 'Enter the post ID\'s to exclude seperated by comma\'s', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['keywords_retrieve_method_exclude_post'] ) ? '' : $attributes['keywords_retrieve_method_exclude_post'] ),
                            'filter'=>true,
                            'parent'=>'keywords_retrieve_method',
                            'filter_value'=>'post_type'
                        ),

                        'keywords_retrieve_method_hide_empty' => array(
                            'name' => __( 'Hide empty categories', 'super-forms' ), 
                            'desc' => __( 'Show or hide empty categories', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['keywords_retrieve_method_hide_empty'] ) ? 0 : $attributes['keywords_retrieve_method_hide_empty'] ),
                            'type' => 'select', 
                            'filter'=>true,
                            'values' => array(
                                0 => __( 'Disabled', 'super-forms' ), 
                                1 => __( 'Enabled', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'keywords_retrieve_method',
                            'filter_value'=>'taxonomy'
                        ),
                        'keywords_retrieve_method_parent' => array(
                            'name' => __( 'Based on parent ID', 'super-forms' ), 
                            'desc' => __( 'Retrieve categories by it\'s parent ID (integer only)', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['keywords_retrieve_method_parent'] ) ? '' : $attributes['keywords_retrieve_method_parent'] ),
                            'filter'=>true,
                            'parent'=>'keywords_retrieve_method',
                            'filter_value'=>'taxonomy,post_type'
                        ),
                        'keywords_retrieve_method_value' => array(
                            'name' => __( 'Retrieve Slug, ID or Title as value', 'super-forms' ), 
                            'desc' => __( 'Select if you want to retrieve slug, ID or the title as value', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['keywords_retrieve_method_value'] ) ? 'slug' : $attributes['keywords_retrieve_method_value'] ),
                            'type' => 'select', 
                            'values' => array(
                                'slug' => __( 'Slug (default)', 'super-forms' ), 
                                'id' => __( 'ID', 'super-forms' ),
                                'title' => __( 'Title', 'super-forms' )
                            ),
                            'filter'=>true,
                            'parent'=>'keywords_retrieve_method',
                            'filter_value'=>'taxonomy,post_type,tags'
                        ),
                        'keywords_items' => array(
                            'type' => 'radio_items',
                            'default'=> ( !isset( $attributes['keywords_items'] ) ? 
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
                                ) : $attributes['keywords_items']
                            ),
                            'filter'=>true,
                            'parent'=>'keywords_retrieve_method',
                            'filter_value'=>'custom'
                        ),



                        /*
                        'keywords_tags' => array(
                            'desc' => __( 'When user starts typing it will autosuggest with existing wordpress tags', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['keywords_tags'] ) ? '' : $attributes['keywords_tags'] ),
                            'type' => 'checkbox', 
                            'values' => array(
                                'true' => __( 'Enable autosuggestion based on wordpress tags', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'enable_keywords',
                            'filter_value'=>'true'
                        ),
                        'keywords_tags_value' => array(
                            'name' => __( 'Retrieve Tag slug, ID or title as value', 'super-forms' ), 
                            'desc' => __( 'Select if you want to retrieve slug, ID or the title as value', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['keywords_tags_value'] ) ? 'slug' : $attributes['keywords_tags_value'] ),
                            'type' => 'select', 
                            'values' => array(
                                'slug' => __( 'Slug (default)', 'super-forms' ), 
                                'id' => __( 'ID', 'super-forms' ),
                                'title' => __( 'Title', 'super-forms' )
                            ),
                            'filter'=>true,
                            'parent'=>'keywords_tags',
                            'filter_value'=>'true'
                        ),
                        */

                        'keyword_max' => array(
                            'name' => __( 'Maximum allowed keywords', 'super-forms' ), 
                            'desc' => __( 'Set a keyword limit for the user to enter', 'super-forms' ), 
                            'type' => 'slider', 
                            'default'=> ( !isset( $attributes['keyword_max'] ) ? 5 : $attributes['keyword_max'] ),
                            'min' => 0,
                            'max' => 20,
                            'steps' => 1,
                            'filter'=>true,
                            'parent'=>'enable_keywords',
                            'filter_value'=>'true'
                        ),
                        'keyword_split_method' => array(
                            'name' => __( 'Keywords split method (default=both)', 'super-forms' ), 
                            'desc' => __( 'Select to split words by comma or space or both', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['keyword_split_method'] ) ? 'both' : $attributes['keyword_split_method'] ),
                            'type' => 'select', 
                            'values' => array(
                                'comma' => __( '"," (comma only)', 'super-forms' ), 
                                'space' => __( '" " (space only)', 'super-forms' ),
                                'both' => __( 'Both (comma and space)', 'super-forms' )
                            ),
                            'filter'=>true,
                            'parent'=>'keywords_retrieve_method',
                            'filter_value'=>'free'
                        ),
                    ),
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
                        // @since 3.2.0 - skip specific field from being autopopulated after a successfull search result
                        'search_skip' => array(
                            'name' => __( 'Fields to skip (enter unique field names seperated by pipes)', 'super-forms' ), 
                            'label' => __( 'Example: first_name|last_name|email', 'super-forms' ), 
                            'desc' => __( 'Do not fill out the following field with entry data:', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['search_skip'] ) ? '' : $attributes['search_skip'] ),
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
                        'readonly' => $readonly,
                        'autocomplete' => $autocomplete,
                        'grouped' => $grouped,
                        'mask' => array(
                            'default'=> ( !isset( $attributes['mask'] ) ? '' : $attributes['mask'] ),
                            'name' => __( 'Enter a predefined mask e.g: (999) 999-9999', 'super-forms' ), 
                            'label' => __( '(leave blank for no input mask)<br />a - Represents an alpha character (A-Z,a-z)<br />9 - Represents a numeric character (0-9)<br />* - Represents an alphanumeric character (A-Z,a-z,0-9)', 'super-forms' ),
                        ),
                        'uppercase' => array(
                            'name' => __( 'Automatically transform text to uppercase', 'super-forms' ),
                            'label' => __( 'User input will automatically be converted into uppercase text', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['uppercase'] ) ? '' : $attributes['uppercase'] ),
                            'type' => 'checkbox', 
                            'values' => array(
                                'true' => __( 'Enable uppercase transformation', 'super-forms' ),
                            )
                        ),
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'maxnumber' => $maxnumber,
                        'minnumber' => $minnumber,
                        'width' => $width,
                        'wrapper_width' => $wrapper_width,
                        'exclude' => $exclude,
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
                        'error_position' => $error_position,

                        // @since 3.2.0 - custom TAB index
                        'custom_tab_index' => $custom_tab_index,

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

        'textarea_predefined' => array(
            'name' => __( 'Text area', 'super-forms' ),
            'icon' => 'list-alt',
            'predefined' => array(
                array(
                    'tag' => 'textarea',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'question', 'super-forms' ),
                        'email' => __( 'Question:', 'super-forms' ),
                        'placeholder' => __( 'Ask us any questions...', 'super-forms' ),
                        'icon' => 'question',
                    )
                )
            ),
            'atts' => array(),
        ),
        'textarea' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::textarea',
            'name' => __( 'Text area', 'super-forms' ),
            'icon' => 'list-alt',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, '' ),
                        'email' => SUPER_Shortcodes::email($attributes, '' ),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes, '' ),
                        'value' => array(
                            'name' => __( 'Default value', 'super-forms' ), 
                            'desc' => __( 'Set a default value for this field. {post_id}, {post_title} and {user_****} can be used (leave blank for none)', 'super-forms' ),
                            'type' => 'textarea',
                            'default'=> ( !isset( $attributes['value'] ) ? '' : $attributes['value'] ),
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
                        'readonly' => $readonly,
                        'autocomplete' => $autocomplete,
                        'grouped' => $grouped,
                        'maxlength' => array(
                            'name' => __( 'Max characters/selections allowed', 'super-forms' ), 
                            'label' => __( 'Please note: The textarea max length setting will not cut off the user from being able to type beyond the limitation. This is for user friendly purposes to avoid text being cut of when a user tries to copy/paste text that would exceed the limit (which would be annoying in some circumstances).', 'super-forms' ),
                            'desc' => __( 'Set to 0 to remove limitations.', 'super-forms' ),
                            'type' => 'slider', 
                            'default'=> (!isset($attributes['maxlength']) ? 0 : $attributes['maxlength']),
                            'min' => 0, 
                            'max' => 100, 
                            'steps' => 1 
                        ),
                        'minlength' => $minlength,
                        'width' => $width,
                        'wrapper_width' => $wrapper_width,
                        'height' => $height,                    
                        'exclude' => $exclude,
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
                        'error_position' => $error_position,

                        // @since 3.2.0 - custom TAB index
                        'custom_tab_index' => $custom_tab_index,                        

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
                        'icon' => SUPER_Shortcodes::icon($attributes,''),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),

        'dropdown_predefined' => array(
            'name' => __( 'Dropdown', 'super-forms' ),
            'icon' => 'caret-square-o-down',
            'predefined' => array(
                array(
                    'tag' => 'dropdown',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'option', 'super-forms' ),
                        'email' => __( 'Option:', 'super-forms' ),
                        'placeholder' => __( '- select a option -', 'super-forms' ),
                        'icon' => 'toggle-down',
                        'dropdown_items' => array(
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
                        )
                    )
                )
            ),
            'atts' => array(),
        ),
        'dropdown' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::dropdown',
            'name' => __( 'Dropdown', 'super-forms' ),
            'icon' => 'caret-square-o-down',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, ''),
                        'email' => SUPER_Shortcodes::email($attributes, ''),
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
                            'filter_value'=>'csv',
                            'file_type'=>'text/csv'
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

                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes, '' ),
                        'tooltip' => $tooltip,
                        'validation' => $validation_empty,
                        'error' => $error
                    )
                ),

                // @since 3.5.0 - google distance calculation between 2 addresses for dropdowns
                // Example GET request: http://maps.googleapis.com/maps/api/directions/json?gl=uk&units=imperial&origin=Ulft&destination=7064BW
                'distance_calculator' => array(
                    'name' => __( 'Distance / Duration calculation (google directions)', 'super-forms' ),
                    'fields' => array(
                        'enable_distance_calculator' => array(
                            'desc' => __( 'Wether or not to use the distance calculator feature', 'super-forms' ), 
                            'label' => __( 'If you enable this option, make sure you have set your <strong>Google API key</strong> under "Super Forms > Settings > Form Settings"', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['enable_distance_calculator'] ) ? '' : $attributes['enable_distance_calculator'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Enable distance calculator', 'super-forms' ),
                            )
                        ),
                        'distance_method' => array(
                            'name' => __( 'Select if this field must act as Start or Destination', 'super-forms' ), 
                            'desc' => __( 'This option is required so that Super Forms knows how to calculate the distance', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['distance_method'] ) ? 'start' : $attributes['distance_method'] ),
                            'type' => 'select', 
                            'values' => array(
                                'start' => __( 'Start address', 'super-forms' ), 
                                'destination' => __( 'Destination address', 'super-forms' )
                            ),
                            'filter'=>true,
                            'parent'=>'enable_distance_calculator',
                            'filter_value'=>'true'
                        ),
                        'distance_start' => array(
                            'name' => __( 'Starting address (required)', 'super-forms' ), 
                            'label' => __( 'Enter a fixed address/zipcode or enter the unique field name to retrieve dynamic address from users', 'super-forms' ),
                            'desc' => __( 'Required to calculate distance between 2 locations', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['start'] ) ? '' : $attributes['start'] ),
                            'filter'=>true,
                            'parent'=>'distance_method',
                            'filter_value'=>'destination'
                        ),
                        'distance_destination' => array(
                            'name' => __( 'Destination address (required)', 'super-forms' ), 
                            'label' => __( 'Enter a fixed address/zipcode or enter the unique field name to retrieve dynamic address from users', 'super-forms' ),
                            'desc' => __( 'Required to calculate distance between 2 locations', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['destination'] ) ? '' : $attributes['destination'] ),
                            'filter'=>true,
                            'parent'=>'distance_method',
                            'filter_value'=>'start'
                        ),
                        'distance_value' => array(
                            'name' => __( 'Select what value to return (distance or duration)', 'super-forms' ), 
                            'desc' => __( 'After calculating the distance either the amount of meters or seconds can be returned', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['distance_value'] ) ? 'distance' : $attributes['distance_value'] ),
                            'type' => 'select', 
                            'values' => array(
                                'distance' => __( 'Distance in meters', 'super-forms' ), 
                                'duration' => __( 'Duration in seconds', 'super-forms' ),
                                'dis_text' => __( 'Distance text in km or miles', 'super-forms' ), 
                                'dur_text' => __( 'Duration text in minutes', 'super-forms' )
                            ),
                            'filter'=>true,
                            'parent'=>'distance_method',
                            'filter_value'=>'start'
                        ),
                        'distance_units' => array(
                            'name' => __( 'Select a unit system', 'super-forms' ), 
                            'desc' => __( 'This will determine if the textual distance is returned in meters or miles', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['distance_units'] ) ? 'metric' : $attributes['distance_units'] ),
                            'type' => 'select', 
                            'values' => array(
                                'metric' => __( 'Metric (distance returned in kilometers and meters)', 'super-forms' ), 
                                'imperial' => __( 'Imperial (distance returned in miles and feet)', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'distance_value',
                            'filter_value'=>'dis_text'
                        ),
                        'distance_field' => array(
                            'name' => __( 'Enter the unique field name which the distance value should be populated to (required)', 'super-forms' ), 
                            'label' => __( 'This can be a Text field or Hidden field (do not add brackets before and after).', 'super-forms' ),
                            'desc' => __( 'After doing the calculation the value will be populated to this field', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['distance_field'] ) ? '' : $attributes['distance_field'] ),
                            'filter'=>true,
                            'parent'=>'distance_method',
                            'filter_value'=>'start'
                        ),
                    )
                ),

                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'readonly' => $readonly,
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'grouped' => $grouped,
                        'width' => $width,                   
                        'wrapper_width' => $wrapper_width,
                        'exclude' => $exclude,
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
                        'error_position' => $error_position_left_only,

                        // @since 3.2.0 - custom TAB index
                        'custom_tab_index' => $custom_tab_index,

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
                        'icon' => SUPER_Shortcodes::icon($attributes,''),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array

            ),
        ),
        'dropdown_item' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::dropdown_item',
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

        'checkbox_predefined' => array(
            'name' => __( 'Check box', 'super-forms' ),
            'icon' => 'check-square-o',
            'predefined' => array(
                array(
                    'tag' => 'checkbox',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'option', 'super-forms' ),
                        'email' => __( 'Option:', 'super-forms' ),
                        'icon' => 'check-square-o',
                        'checkbox_items' => array(
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
                        )
                    )
                )
            ),
            'atts' => array(),
        ),
        'checkbox' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::checkbox',
            'name' => __( 'Check box', 'super-forms' ),
            'icon' => 'check-square-o',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, ''),
                        'email' => SUPER_Shortcodes::email($attributes, ''),
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
                            'filter_value'=>'csv',
                            'file_type'=>'text/csv'
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
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
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
                        'icon' => SUPER_Shortcodes::icon($attributes,''),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'checkbox_item' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::checkbox_item',
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

        'radio_predefined' => array(
            'name' => __( 'Radio buttons', 'super-forms' ),
            'icon' => 'dot-circle-o',
            'predefined' => array(
                array(
                    'tag' => 'radio',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'option', 'super-forms' ),
                        'email' => __( 'Option:', 'super-forms' ),
                        'icon' => 'dot-circle-o',
                        'radio_items' => array(
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
                        )
                    )
                )
            ),
            'atts' => array(),
        ),
        'radio' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::radio',
            'name' => __( 'Radio buttons', 'super-forms' ),
            'icon' => 'dot-circle-o',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, '' ),
                        'email' => SUPER_Shortcodes::email($attributes, '' ),
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
                            'filter_value'=>'csv',
                            'file_type'=>'text/csv'
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
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
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
                        'icon' => SUPER_Shortcodes::icon($attributes,''),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'radio_item' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::radio_item',
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

        'quantity_predefined' => array(
            'name' => __( 'Quantity field', 'super-forms' ),
            'icon' => 'plus-square',
            'predefined' => array(
                array(
                    'tag' => 'quantity',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'quantity', 'super-forms' ),
                        'email' => __( 'Quantity:', 'super-forms' ),
                        'value' => '0'
                    )
                )
            ),
            'atts' => array(),
        ),
        'quantity' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::quantity_field',
            'name' => __( 'Quantity field', 'super-forms' ),
            'icon' => 'plus-square',
            'atts' => array(
                 'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, ''),
                        'email' => SUPER_Shortcodes::email($attributes, ''),
                        'label' => $label,
                        'description'=>$description,                    
                        'value' => array(
                            'default'=> ( !isset( $attributes['value'] ) ? '' : $attributes['value'] ),
                            'name' => __( 'Default value', 'super-forms' ), 
                            'desc' => __( 'Set a default value for this field (leave blank for none)', 'super-forms' )
                        ),
                        'tooltip' => $tooltip,
                        'conditional_validation' => $conditional_validation,
                        'conditional_validation_value' => $conditional_validation_value,
                        'conditional_validation_value2' => $conditional_validation_value2, // @since 3.6.0
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
                            'max' => 50,
                            'steps' => 0.5,
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
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
                        'error_position' => $error_position,

                        // @since 3.2.0 - custom TAB index
                        'custom_tab_index' => $custom_tab_index,

                        // @since 1.9
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,

                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),

        // @since 2.9.0 - toggle butotn
        'toggle_predefined' => array(
            'name' => __( 'Toggle field', 'super-forms' ),
            'icon' => 'toggle-on',
            'predefined' => array(
                array(
                    'tag' => 'toggle',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'quantity', 'super-forms' ),
                        'email' => __( 'Quantity:', 'super-forms' ),
                        'icon' => 'user',
                        'value' => '0'
                    )
                )
            ),
            'atts' => array(),
        ),
        'toggle' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::toggle_field',
            'name' => __( 'Toggle field', 'super-forms' ),
            'icon' => 'toggle-on',
            'atts' => array(
                 'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, ''),
                        'email' => SUPER_Shortcodes::email($attributes, ''),
                        'label' => $label,
                        'description'=>$description,

                        'prefix_label' => array(
                            'name'=>__( 'Prefix label', 'super-forms' ), 
                            'desc'=>__( 'Text on left side of the toggle button (leave blank for no text)', 'super-forms' ),
                            'default'=> (!isset($attributes['prefix_label']) ? '' : $attributes['prefix_label']),
                        ),
                        'prefix_tooltip' => array(
                            'name'=>__( 'Prefix question icon tooltip text', 'super-forms' ), 
                            'label'=>__( 'Leave blank for no question icon', 'super-forms' ), 
                            'desc'=>__( 'This will add a question mark with a tooltip (leave blank for no question icon)', 'super-forms' ),
                            'default'=> (!isset($attributes['prefix_tooltip']) ? '' : $attributes['prefix_tooltip']),
                        ),
                        'suffix_label' => array(
                            'name'=>__( 'Suffix label', 'super-forms' ), 
                            'desc'=>__( 'Text on right side of the toggle button (leave blank for no text)', 'super-forms' ),
                            'default'=> (!isset($attributes['suffix_label']) ? '' : $attributes['suffix_label']),
                        ),
                        'suffix_tooltip' => array(
                            'name'=>__( 'Suffix question icon tooltip text', 'super-forms' ), 
                            'label'=>__( 'Leave blank for no question icon', 'super-forms' ), 
                            'desc'=>__( 'This will add a question mark with a tooltip (leave blank for no question icon)', 'super-forms' ),
                            'default'=> (!isset($attributes['suffix_tooltip']) ? '' : $attributes['suffix_tooltip']),
                        ),

                        'value' => array(
                            'name' => __( 'Toggle start value (default status)', 'super-forms' ), 
                            'desc' => __( 'Select the toggle default status', 'super-forms' ),
                            'default'=> (!isset($attributes['value']) ? '0' : $attributes['value']),
                            'type'=>'select', 
                            'values'=>array(
                                '1' => __( 'On (toggle enabled)', 'super-forms' ),
                                '0' => __( 'Off (toggle disabled)', 'super-forms' ),
                            )
                        ),
                        'on_value' => array(
                            'default'=> ( !isset( $attributes['value'] ) ? 'on' : $attributes['value'] ),
                            'name' => __( '"On" value', 'super-forms' ), 
                            'desc' => __( 'This is the toggle value when the user enabled the toggle element', 'super-forms' ),
                        ),
                        'on_label' => array(
                            'default'=> ( !isset( $attributes['value'] ) ? 'On' : $attributes['value'] ),
                            'name' => __( '"On" label', 'super-forms' ), 
                            'desc' => __( 'This is the toggle label when the user enabled the toggle element', 'super-forms' ),
                        ),
                        'off_value' => array(
                            'default'=> ( !isset( $attributes['value'] ) ? 'off' : $attributes['value'] ),
                            'name' => __( '"Off" value', 'super-forms' ), 
                            'desc' => __( 'This is the toggle value when the user disabled the toggle element', 'super-forms' ),
                        ),
                        'off_label' => array(
                            'default'=> ( !isset( $attributes['value'] ) ? 'Off' : $attributes['value'] ),
                            'name' => __( '"Off" label', 'super-forms' ), 
                            'desc' => __( 'This is the toggle label when the user disabled the toggle element', 'super-forms' ),
                        ),
                        'tooltip' => $tooltip,
                        'conditional_validation' => $conditional_validation,
                        'conditional_validation_value' => $conditional_validation_value,
                        'conditional_validation_value2' => $conditional_validation_value2, // @since 3.6.0
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'width' => $width,
                        'wrapper_width' => $wrapper_width,
                        'exclude' => $exclude,
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
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
                        'icon' => SUPER_Shortcodes::icon($attributes,''),
                    ),
                ),                
                'conditional_logic' => $conditional_logic_array
            ),
        ),

        // @since 3.1.0 - color picker element
        'color_predefined' => array(
            'name' => __( 'Color picker', 'super-forms' ),
            'icon' => 'eyedropper',
            'predefined' => array(
                array(
                    'tag' => 'color',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'color', 'super-forms' ),
                        'email' => __( 'Color:', 'super-forms' ),
                        'icon' => 'user',
                    )
                )
            ),
            'atts' => array(),
        ),
        'color' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::color',
            'name' => __( 'Color picker', 'super-forms' ),
            'icon' => 'eyedropper',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, ''),
                        'email' => SUPER_Shortcodes::email($attributes, ''),
                        'label' => $label,
                        'description'=>$description,
                        'prefix_label' => array(
                            'name'=>__( 'Prefix label', 'super-forms' ), 
                            'desc'=>__( 'Text on left side of the color picker (leave blank for no text)', 'super-forms' ),
                            'default'=> (!isset($attributes['prefix_label']) ? '' : $attributes['prefix_label']),
                        ),
                        'prefix_tooltip' => array(
                            'name'=>__( 'Prefix question icon tooltip text', 'super-forms' ), 
                            'label'=>__( 'Leave blank for no question icon', 'super-forms' ), 
                            'desc'=>__( 'This will add a question mark with a tooltip (leave blank for no question icon)', 'super-forms' ),
                            'default'=> (!isset($attributes['prefix_tooltip']) ? '' : $attributes['prefix_tooltip']),
                        ),
                        'suffix_label' => array(
                            'name'=>__( 'Suffix label', 'super-forms' ), 
                            'desc'=>__( 'Text on right side of the color picker (leave blank for no text)', 'super-forms' ),
                            'default'=> (!isset($attributes['suffix_label']) ? '' : $attributes['suffix_label']),
                        ),
                        'suffix_tooltip' => array(
                            'name'=>__( 'Suffix question icon tooltip text', 'super-forms' ), 
                            'label'=>__( 'Leave blank for no question icon', 'super-forms' ), 
                            'desc'=>__( 'This will add a question mark with a tooltip (leave blank for no question icon)', 'super-forms' ),
                            'default'=> (!isset($attributes['suffix_tooltip']) ? '' : $attributes['suffix_tooltip']),
                        ),
                        'value' => array(
                            'default'=> ( !isset( $attributes['value'] ) ? '' : $attributes['value'] ),
                            'name' => __( 'Default value', 'super-forms' ), 
                            'desc' => __( 'Set a default color (leave blank for none)', 'super-forms' )
                        ),
                        'tooltip' => $tooltip,
                        'validation' => $special_validations,
                        'custom_regex' => $custom_regex,
                        'conditional_validation' => $conditional_validation,
                        'conditional_validation_value' => $conditional_validation_value,
                        'conditional_validation_value2' => $conditional_validation_value2, // @since 3.6.0
                        'may_be_empty' => $may_be_empty,
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'disabled' => $disabled,
                        'readonly' => $readonly,
                        'autocomplete' => $autocomplete,
                        'grouped' => $grouped,
                        'uppercase' => array(
                            'name' => __( 'Automatically transform text to uppercase', 'super-forms' ),
                            'label' => __( 'User input will automatically be converted into uppercase text', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['uppercase'] ) ? '' : $attributes['uppercase'] ),
                            'type' => 'checkbox', 
                            'values' => array(
                                'true' => __( 'Enable uppercase transformation', 'super-forms' ),
                            )
                        ),
                        'width' => $width,
                        'wrapper_width' => $wrapper_width,
                        'exclude' => $exclude,
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
                        'error_position' => $error_position,

                        // @since 3.2.0 - custom TAB index
                        'custom_tab_index' => $custom_tab_index,

                        'class' => $class,
                        'wrapper_class' => $wrapper_class,
                    ),
                ),
                'icon' => array(
                    'name' => __( 'Icon', 'super-forms' ),
                    'fields' => array(
                        'icon_position' => $icon_position,
                        'icon_align' => $icon_align,
                        'icon' => SUPER_Shortcodes::icon($attributes,''),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),

        'slider_predefined' => array(
            'name' => __( 'Slider field', 'super-forms' ),
            'icon' => 'sliders',
            'predefined' => array(
                array(
                    'tag' => 'slider',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'amount', 'super-forms' ),
                        'email' => __( 'Amount:', 'super-forms' ),
                        'value' => '0',
                        'currency' => '$',
                        'thousand_separator' => ',',
                        'icon' => 'user',
                    )
                )
            ),
            'atts' => array(),
        ),
        'slider' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::slider_field',
            'name' => __( 'Slider field', 'super-forms' ),
            'icon' => 'sliders',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, ''),
                        'email' => SUPER_Shortcodes::email($attributes, ''),
                        'label' => $label,
                        'description'=>$description,
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
                            'default'=> ( !isset( $attributes['currency'] ) ? '' : $attributes['currency'] ),
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
                            'default'=> (!isset($attributes['thousand_separator']) ? '' : $attributes['thousand_separator']),
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
                        'conditional_validation_value2' => $conditional_validation_value2, // @since 3.6.0
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
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
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
                        'icon' => SUPER_Shortcodes::icon($attributes,''),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),

        // @since 2.1.0
        'currency_predefined' => array(
            'name' => __( 'Currency field', 'super-forms' ),
            'icon' => 'usd',
            'predefined' => array(
                array(
                    'tag' => 'currency',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'amount', 'super-forms' ),
                        'email' => __( 'Amount:', 'super-forms' ),
                        'currency' => '$',
                        'thousand_separator' => ',',
                        'icon' => 'user',
                    )
                )
            ),
            'atts' => array(),
        ),
        'currency' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::currency',
            'name' => __( 'Currency field', 'super-forms' ),
            'icon' => 'usd',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, ''),
                        'email' => SUPER_Shortcodes::email($attributes, ''),
                        'label' => $label,
                        'description'=>$description,
                        
                        // @deprecated since 2.8.0 - currency fields no longer support placeholders due to validating the value and only allowing currency formats
                        //'placeholder' => SUPER_Shortcodes::placeholder( $attributes, __( '$0.00', 'super-forms' ) ),
                        
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
                            'default'=> ( !isset( $attributes['currency'] ) ? '' : $attributes['currency'] ),
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
                            'default'=> (!isset($attributes['thousand_separator']) ? '' : $attributes['thousand_separator']),
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
                        'conditional_validation_value2' => $conditional_validation_value2, // @since 3.6.0
                        'may_be_empty' => $may_be_empty,
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'disabled' => $disabled,
                        'readonly' => $readonly,
                        'autocomplete' => $autocomplete,
                        'grouped' => $grouped,
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'maxnumber' => $maxnumber,
                        'minnumber' => $minnumber,
                        'width' => $width,
                        'wrapper_width' => $wrapper_width,
                        'exclude' => $exclude,
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
                        'error_position' => $error_position,

                        // @since 3.2.0 - custom TAB index
                        'custom_tab_index' => $custom_tab_index,

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
                        'icon' => SUPER_Shortcodes::icon($attributes,''),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),

        'file_predefined' => array(
            'name' => __( 'File upload', 'super-forms' ),
            'icon' => 'download',
            'predefined' => array(
                array(
                    'tag' => 'file',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'file', 'super-forms' ),
                        'email' => __( 'File:', 'super-forms' ),
                        'icon' => 'download',
                    )
                )
            ),
            'atts' => array(),
        ),
        'file' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::file',
            'name' => __( 'File upload', 'super-forms' ),
            'icon' => 'download',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, ''),
                        'email' => SUPER_Shortcodes::email($attributes, ''),
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
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
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
                        'icon' => SUPER_Shortcodes::icon($attributes,''),
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
                    'data' => array(
                        'name' => __( 'phonenumber', 'super-forms' ),
                        'email' => __( 'Phonenumber:', 'super-forms' ),
                        'placeholder' => __( 'Your Phonenumber', 'super-forms' ),
                        'validation' => 'phone',
                        'icon' => 'phone',
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
                    'data' => array(
                        'name' => __( 'website', 'super-forms' ),
                        'email' => __( 'Website:', 'super-forms' ),
                        'placeholder' => __( 'http://', 'super-forms' ),
                        'validation' => 'website',
                        'icon' => 'link',
                    )
                )
            ),
            'atts' => array(),
        ),

        'date_predefined' => array(
            'name' => __( 'Date', 'super-forms' ),
            'icon' => 'calendar',
            'predefined' => array(
                array(
                    'tag' => 'date',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'date', 'super-forms' ),
                        'email' => __( 'Date:', 'super-forms' ),
                        'placeholder' => __( 'Select a date', 'super-forms' ),
                        'icon' => 'calendar',
                        'work_days' => 'true',
                        'weekends' => 'true',
                    )
                )
            ),
            'atts' => array(),
        ),
        'date' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::date',
            'name' => __( 'Date', 'super-forms' ),
            'icon' => 'calendar',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, ''),
                        'email' => SUPER_Shortcodes::email($attributes, ''),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes, ''),
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
                            ),
                            'allow_empty' => true, // For backward compatibility with older forms
                        ),
                        'weekends' => array(
                            'default'=> ( !isset( $attributes['weekends'] ) ? 'true' : $attributes['weekends'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Allow users to select weekends', 'super-forms' ),
                            ),
                            'allow_empty' => true, // For backward compatibility with older forms
                        ),
                        // @since 3.6.0 - excl specific days from calendar
                        'excl_days' => array(
                            'name' => __( 'Exclude specific days from being selected by user', 'super-forms' ),
                            'label' => __( 'Use numbers to specify days to exclude seperated by comma\'s e.g: 0,1,2<br />Where: 0 = Sunday and 1 = Monday etc.', 'super-forms' ),
                            'desc' => __( 'Disable the option to select the specific day in the calendar e.g Sunday, Monday etc.', 'super-forms' ),
                            'default'=> ( !isset( $attributes['excl_days'] ) ? '' : $attributes['excl_days'] ),
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

                        // @since 3.1.0 - option to change the first day of the week on date picker element
                        'first_day' => array(
                            'name'=>__( 'First day of week', 'super-forms' ), 
                            'desc'=>__( 'Change the first day of the week e.g Sunday or Monday', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['first_day']) ? '1' : $attributes['first_day']),
                            'type'=>'select', 
                            'values'=>array(
                                '1' => __( 'Monday (default)', 'super-forms' ),
                                '2' => __( 'Tuesday', 'super-forms' ),
                                '3' => __( 'Wednesday', 'super-forms' ),
                                '4' => __( 'Thursday', 'super-forms' ),
                                '5' => __( 'Friday', 'super-forms' ),
                                '6' => __( 'Saturday', 'super-forms' ),
                                '0' => __( 'Sunday', 'super-forms' ),
                            )
                        ),

                        'validation' => $validation_empty,
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'disabled' => $disabled,
                        'autocomplete' => $autocomplete,
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
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
                        'error_position' => $error_position,

                        // @since 3.2.0 - custom TAB index
                        'custom_tab_index' => $custom_tab_index,

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
                        'icon' => SUPER_Shortcodes::icon($attributes,''),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),

        'time_predefined' => array(
            'name' => __( 'Time', 'super-forms' ),
            'icon' => 'clock-o',
            'predefined' => array(
                array(
                    'tag' => 'time',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'time', 'super-forms' ),
                        'email' => __( 'Time:', 'super-forms' ),
                        'placeholder' => __( 'Select a time', 'super-forms' ),
                        'icon' => 'clock-o',
                    )
                )
            ),
            'atts' => array(),
        ),
        'time' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::time',
            'name' => __( 'Time', 'super-forms' ),
            'icon' => 'clock-o',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, ''),
                        'email' => SUPER_Shortcodes::email($attributes, ''),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes, ''),
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
                        'readonly' => $readonly,
                        'autocomplete' => $autocomplete,
                        'grouped' => $grouped,
                        'width' => SUPER_Shortcodes::width($attributes, $default=0),
                        'exclude' => $exclude,
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
                        'error_position' => $error_position,

                        // @since 3.2.0 - custom TAB index
                        'custom_tab_index' => $custom_tab_index,
                                                
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
                        'icon' => SUPER_Shortcodes::icon($attributes,''),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),

        'rating_predefined' => array(
            'name' => __( 'Rating', 'super-forms' ),
            'icon' => 'star-o',
            'predefined' => array(
                array(
                    'tag' => 'rating',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'rating', 'super-forms' ),
                        'email' => __( 'Rating:', 'super-forms' ),
                        'icon' => 'heart',
                    )
                )
            ),
            'atts' => array(),
        ),
        'rating' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::rating',
            'name' => __( 'Rating', 'super-forms' ),
            'icon' => 'star-o',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, ''),
                        'email' => SUPER_Shortcodes::email($attributes, ''),
                        'label' => $label,
                        'description'=>$description,
                        'value' => array(
                            'name' => __( 'Default value 1-5 (empty = default)', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['value'] ) ? '' : $attributes['value'] )
                        ),
                        'tooltip' => $tooltip,
                        'validation' => $validation_not_empty,
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'width' => SUPER_Shortcodes::width($attributes, $default=0),
                        'exclude' => $exclude,
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
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
                        'icon' => SUPER_Shortcodes::icon($attributes,''),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),

        'skype_predefined' => array(
            'name' => __( 'Skype', 'super-forms' ),
            'icon' => 'skype',
            'predefined' => array(
                array(
                    'tag' => 'skype',
                    'group' => 'form_elements',
                    'data' => array(
                        'method' => 'call'
                    )
                )
            ),
            'atts' => array(),
        ),
        'skype' => array(
            'hidden' => true,
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

        'countries_predefined' => array(
            'name' => __( 'Countries', 'super-forms' ),
            'icon' => 'globe',
            'predefined' => array(
                array(
                    'tag' => 'countries',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'country', 'super-forms' ),
                        'email' => __( 'Country:', 'super-forms' ),
                        'placeholder' => __( '- select your country -', 'super-forms' ),
                        'icon' => 'globe'
                    )
                )
            ),
            'atts' => array(),
        ),
        'countries' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::countries',
            'name' => __( 'Countries', 'super-forms' ),
            'icon' => 'globe',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name( $attributes, '' ),
                        'email' => SUPER_Shortcodes::email( $attributes, '' ),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder( $attributes, '' ),
                        'tooltip' => $tooltip,
                        'validation' => $special_validations,
                        'custom_regex' => $custom_regex,
                        'conditional_validation' => $conditional_validation,
                        'conditional_validation_value' => $conditional_validation_value,
                        'conditional_validation_value2' => $conditional_validation_value2, // @since 3.6.0
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
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
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
                        'icon' => SUPER_Shortcodes::icon($attributes,''),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),

        'password_predefined' => array(
            'name' => __( 'Password field', 'super-forms' ),
            'icon' => 'lock',
            'predefined' => array(
                array(
                    'tag' => 'password',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'password', 'super-forms' ),
                        'email' => __( 'Password:', 'super-forms' ),
                        'placeholder' => __( 'Enter a strong password', 'super-forms' ),
                        'icon' => 'lock'
                    )
                )
            ),
            'atts' => array(),
        ),
        'password' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::password',
            'name' => __( 'Password field', 'super-forms' ),
            'icon' => 'lock',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, ''),
                        'email' => SUPER_Shortcodes::email($attributes, ''),
                        'label' => $label,
                        'description'=>$description,
                        'placeholder' => SUPER_Shortcodes::placeholder($attributes,''),
                        'tooltip' => $tooltip,
                        'validation' => $special_validations,
                        'custom_regex' => $custom_regex,
                        'conditional_validation' => $conditional_validation,
                        'conditional_validation_value' => $conditional_validation_value,
                        'conditional_validation_value2' => $conditional_validation_value2, // @since 3.6.0
                        'may_be_empty' => $may_be_empty,
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'disabled' => $disabled,
                        'readonly' => $readonly,
                        'autocomplete' => $autocomplete,
                        'grouped' => $grouped,
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'width' => $width,
                        'wrapper_width' => $wrapper_width,
                        'exclude' => $exclude,
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
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
                        'icon' => SUPER_Shortcodes::icon($attributes,''),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),

        'hidden_predefined' => array(
            'name' => __( 'Hidden field', 'super-forms' ),
            'icon' => 'eye-slash',
            'predefined' => array(
                array(
                    'tag' => 'hidden',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'hidden', 'super-forms' ),
                        'email' => __( 'Hidden:', 'super-forms' )
                    )
                )
            ),
            'atts' => array(),
        ),
        'hidden' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::hidden',
            'name' => __( 'Hidden field', 'super-forms' ),
            'icon' => 'eye-slash',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name( $attributes, '' ),
                        'email' => SUPER_Shortcodes::email( $attributes, '' ),
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
                        'exclude_entry' => $exclude_entry, // @since 3.3.0 - exclude data from being saved into contact entry
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

                        // @since 2.8.0 - invoice numbers
                        'code_invoice' => array(
                            'default'=> ( !isset( $attributes['code_invoice'] ) ? '' : $attributes['code_invoice'] ),
                            'type' => 'checkbox', 
                            'values' => array(
                                'true' => __( 'Enable invoice numbers increament e.g: 0001', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'enable_random_code',
                            'filter_value'=>'true'
                        ),
                        'code_invoice_padding' => array(
                            'name'=>__( 'Invoice number padding (leading zero\'s)', 'super-forms' ),
                            'label' => __( 'Enter "4" to display 16 as 0016', 'super-forms' ),
                            'default'=> ( !isset( $attributes['code_invoice_padding']) ? '4' : $attributes['code_invoice_padding']),
                            'filter'=>true,
                            'parent'=>'code_invoice',
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

        'recaptcha_predefined' => array(
            'name' => __( 'reCAPTCHA', 'super-forms' ),
            'icon' => 'shield',
            'predefined' => array(
                array(
                    'tag' => 'recaptcha',
                    'group' => 'form_elements',
                    'data' => array()
                )
            ),
            'atts' => array(),
        ),
        'recaptcha' => array(
            'hidden' => true,
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

        'button_predefined' => array(
            'name' => __( 'Button', 'super-forms' ),
            'icon' => 'hand-o-up',
            'predefined' => array(
                array(
                    'tag' => 'button',
                    'group' => 'form_elements',
                    'data' => array(
                        'name' => __( 'Submit', 'super-forms' ),
                        'loading' => __( 'Loading...', 'super-forms' ),
                    )
                )
            ),
            'atts' => array(),
        ),
        'button' => array(
            'hidden' => true,
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
                                'print'=>__( 'Print form data', 'super-forms' ),
                                'url'=>__( 'Redirect to link or URL', 'super-forms' ),
                            ),
                            'filter'=>true,
                        ),
                        'name' => array(
                            'name'=>__( 'Button name', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['name'] ) ? '' : $attributes['name'] ),
                            'parent'=>'action',
                            'filter_value'=>'submit,clear,print,url',
                            'filter'=>true,

                        ),

                        // @since 3.8.0 - option to print with custom HTML/CSS
                        'print_custom' => array(
                            'desc' => __( 'Wether or not to use the auto suggest feature', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['print_custom'] ) ? '' : $attributes['print_custom'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Use custom HTML and CSS when printing', 'super-forms' ),
                            ),
                            'parent'=>'action',
                            'filter_value'=>'print',
                            'filter'=>true,
                        ),
                        'print_file' => array(
                            'name'=>__( 'Custom HTML (upload/browse for .html file)', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['print_file'] ) ? '' : $attributes['print_file'] ),
                            'type'=>'file',
                            'parent'=>'print_custom',
                            'filter_value'=>'true',
                            'filter'=>true,
                        ),
                     

                        // @since 3.4.0 - contact entry statuses
                        'entry_status' => array(
                            'name'=>__( 'Contact entry status after submitting', 'super-forms' ),
                            'desc'=>__( 'What status should the contact entry get after submitting the form?', 'super-forms' ),
                            'default'=> ( !isset( $attributes['entry_status']) ? '' : $attributes['entry_status']),
                            'type'=>'select',
                            'values'=> $statuses,
                            'parent'=>'action',
                            'filter_value'=>'submit',
                            'filter'=>true,
                        ),
                        'entry_status_update' => array(
                            'name'=>__( 'Contact entry status after updating a contact entry', 'super-forms' ),
                            'desc'=>__( 'This will only be usefull if the form updates a previous created entry', 'super-forms' ),
                            'default'=> ( !isset( $attributes['entry_status_update']) ? '' : $attributes['entry_status_update']),
                            'type'=>'select',
                            'values'=> $statuses,
                            'parent'=>'action',
                            'filter_value'=>'submit',
                            'filter'=>true,
                        ),
                        // @since 2.0.0
                        'loading' => array(
                            'name' => __('Button loading name', 'super-forms' ),
                            'default'=> ( !isset( $attributes['loading'] ) ? '' : $attributes['loading'] ),
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

    )
);