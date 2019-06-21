<?php
$array['html_elements'] = array(
    'title' => esc_html__( 'HTML Elements', 'super-forms' ),   
    'class' => 'super-html-elements',
    'shortcodes' => array(
        'image_predefined' => array(
            'name' => esc_html__( 'Image', 'super-forms' ),
            'icon' => 'image;far',
            'predefined' => array(
                array(
                    'tag' => 'image',
                    'group' => 'html_elements',
                    'data' => array(
                        'alignment' => 'left'
                    )
                )
            ),
            'atts' => array(),
        ),
        'image' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::image',
            'name' => esc_html__( 'Image', 'super-forms' ),
            'icon' => 'image;far',
            'atts' => array(
                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'image' => array(
                            'name'=>esc_html__( 'Image', 'super-forms' ),
                            'default'=> ( !isset( $attributes['image']) ? '' : $attributes['image']),
                            'type'=>'image',
                            'i18n' => true
                        ),
                        'width' => array(
                            'type' => 'slider', 
                            'default'=> ( !isset( $attributes['width']) ? 150 : $attributes['width']),
                            'min' => 0, 
                            'max' => 600, 
                            'steps' => 10, 
                            'name' => esc_html__( 'Maximum image width in pixels', 'super-forms' ), 
                            'desc' => esc_html__( 'Set to 0 to use default CSS width.', 'super-forms' )
                        ),
                        'height' => array(
                            'type' => 'slider', 
                            'default'=> ( !isset( $attributes['height']) ? 250 : $attributes['height']),
                            'min' => 0, 
                            'max' => 600, 
                            'steps' => 10, 
                            'name' => esc_html__( 'Maximum image height in pixels', 'super-forms' ), 
                            'desc' => esc_html__( 'Set to 0 to use default CSS width.', 'super-forms' )
                        ),
                        'alignment' => array(
                            'name'=>esc_html__( 'Image Alignment', 'super-forms' ),
                            'desc'=>esc_html__( 'Choose how to align your image', 'super-forms' ),
                            'default'=> ( !isset( $attributes['alignment']) ? '' : $attributes['alignment']),
                            'type'=>'select',
                            'values'=>array(
                                'center'=>esc_html__( 'Center', 'super-forms' ),
                                'left'=>esc_html__( 'Left', 'super-forms' ),
                                'right'=>esc_html__( 'Right', 'super-forms' ),
                                ''=>esc_html__( 'No alignment', 'super-forms' ),
                            )
                        ),
                        'link' => array(
                            'name'=>esc_html__( 'Image Link', 'super-forms' ),
                            'desc'=>esc_html__( 'Where should your image link to?', 'super-forms' ),
                            'default'=> ( !isset( $attributes['link']) ? '' : $attributes['link']),
                            'filter'=>true,
                            'type'=>'select',
                            'values'=>array(
                                ''=>esc_html__( 'No Link', 'super-forms' ),
                                'custom'=>esc_html__( 'Custom URL', 'super-forms' ),
                                'post'=>esc_html__( 'Post', 'super-forms' ),
                                'page'=>esc_html__( 'Page', 'super-forms' ),
                            )
                        ),
                        'custom_link' => array(
                            'name'=>esc_html__( 'Enter a custom URL to link to', 'super-forms' ),
                            'default'=> ( !isset( $attributes['custom_link']) ? '' : $attributes['custom_link']),
                            'parent'=>'link',
                            'filter_value'=>'custom',
                            'filter'=>true,   
                        ),
                        'post' => array(
                            'name'=>esc_html__( 'Select a post to link to', 'super-forms' ),
                            'default'=> ( !isset( $attributes['post']) ? '' : $attributes['post']),
                            'type'=>'select',
                            'values'=>SUPER_Common::list_posts_by_type_array('post'),
                            'parent'=>'link',
                            'filter_value'=>'post',
                            'filter'=>true,    
                        ),
                        'page' => array(
                            'name'=>esc_html__( 'Select a page to link to', 'super-forms' ),
                            'default'=> ( !isset( $attributes['page']) ? '' : $attributes['page']),
                            'type'=>'select',
                            'values'=>SUPER_Common::list_posts_by_type_array('page'),
                            'parent'=>'link',
                            'filter_value'=>'page',
                            'filter'=>true,   
                        ),
                        'target' => array(
                            'name'=>esc_html__( 'Open new tab/window', 'super-forms' ),
                            'default'=> ( !isset( $attributes['target']) ? '' : $attributes['target']),
                            'type'=>'select',
                            'values'=>array(
                                ''=>esc_html__( 'Open in same window', 'super-forms' ),
                                '_blank'=>esc_html__( 'Open in new window', 'super-forms' ),
                            ),
                            'parent'=>'link',
                            'filter_value'=>'custom,post,page',
                            'filter'=>true,
                        ),
                    ),
                ),

                // @since 1.9
                'advanced' => array(
                    'name' => esc_html__( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,
                    ),
                ),

                'conditional_logic' => $conditional_logic_array
            ),
        ),

        'predefined_heading' => array(
            'name' => esc_html__( 'Heading', 'super-forms' ),
            'icon' => 'heading',
            'predefined' => array(
                array(
                    'tag' => 'heading',
                    'group' => 'html_elements',
                    'data' => array(
                        'title' => esc_html__( 'Title', 'super-forms' )
                    )
                )            
            ),
            'atts' => array(),
        ),
        'heading' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::heading',
            'name' => esc_html__( 'Heading', 'super-forms' ),
            'icon' => 'heading',
            'atts' => array(
                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'title' => array(
                            'name' =>esc_html__( 'Title', 'super-forms' ),
                            'default' => ( !isset( $attributes['title']) ? '' : $attributes['title']),
                            'i18n' => true
                        ),
                        'desc' => array(
                            'name'=>esc_html__( 'Description', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc']) ? '' : $attributes['desc']),
                            'i18n' => true
                        ),
                        'size' => array(
                            'name'=>esc_html__( 'Heading size', 'super-forms' ),
                            'default'=> ( !isset( $attributes['title']) ? 'h1' : $attributes['title']),
                            'type'=>'select', 
                            'values'=>array(
                                'h1' => esc_html__( 'Heading 1', 'super-forms' ),
                                'h2' => esc_html__( 'Heading 2', 'super-forms' ),
                                'h3' => esc_html__( 'Heading 3', 'super-forms' ),
                                'h4' => esc_html__( 'Heading 4', 'super-forms' ),
                                'h5' => esc_html__( 'Heading 5', 'super-forms' ),
                                'h6' => esc_html__( 'Heading 6', 'super-forms' ),
                            ),       
                        ),
                    ),
                ),
                'heading_styles' => array(
                    'name' => esc_html__( 'Heading Styles', 'super-forms' ),
                    'fields' => array(
                        'heading_color' => array(
                            'name'=>esc_html__( 'Font color', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_color']) ? '#444444' : $attributes['heading_color']),
                            'type'=>'color',
                        ),
                        'heading_size' => array(
                            'name'=>esc_html__( 'Font size in pixels (0 = default CSS size)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_size']) ? '0' : $attributes['heading_size']),
                            'type'=>'slider',
                            'min'=>0,
                            'max'=>200,
                            'steps'=>1,
                        ),
                        'heading_weight' => array(
                            'name'=>esc_html__( 'Font weight', 'super-forms' ),
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
                            'name'=>esc_html__( 'Text alignment', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_align']) ? 'left' : $attributes['heading_align']),
                            'type'=>'select', 
                            'values'=>array(
                                'left' => esc_html__( 'Left (default)' ,'super-forms' ),
                                'center' => esc_html__( 'Center' ,'super-forms' ),
                                'right' => esc_html__( 'Right' ,'super-forms' ),
                            ),       
                        ),
                        'heading_line_height' => array(
                            'name'=>esc_html__( 'Line height in pixels (0 = normal)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_line_height']) ? '0' : $attributes['heading_line_height']),
                            'type'=>'slider',
                            'min'=>0,
                            'max'=>200,
                            'steps'=>1,
                        ),
                        'heading_margin' => array(
                            'name'=>esc_html__( 'Margins (top right bottom left)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_margin']) ? '0px 0px 0px 0px' : $attributes['heading_margin']),
                            'placeholder' => '0px 0px 0px 0px'
                        ),
                    ),
                ),
                'desc_styles' => array(
                    'name' => esc_html__( 'Description Styles', 'super-forms' ),
                    'fields' => array(
                        'desc_color' => array(
                            'name'=>esc_html__( 'Font color', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_color']) ? '#444444' : $attributes['desc_color']),
                            'type'=>'color',
                        ),
                        'desc_size' => array(
                            'name'=>esc_html__( 'Font size in pixels (0 = default CSS size)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_size']) ? '0' : $attributes['desc_size']),
                            'type'=>'slider',
                            'min'=>0,
                            'max'=>200,
                            'steps'=>1,
                        ),
                        'desc_weight' => array(
                            'name'=>esc_html__( 'Font weight', 'super-forms' ),
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
                            'name'=>esc_html__( 'Text alignment', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_align']) ? 'left' : $attributes['desc_align']),
                            'type'=>'select', 
                            'values'=>array(
                                'left' => esc_html__( 'Left (default)' ,'super-forms' ),
                                'center' => esc_html__( 'Center' ,'super-forms' ),
                                'right' => esc_html__( 'Right' ,'super-forms' ),
                            ),       
                        ),
                        'desc_line_height' => array(
                            'name'=>esc_html__( 'Line height in pixels (0 = normal)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_line_height']) ? '0' : $attributes['desc_line_height']),
                            'type'=>'slider',
                            'min'=>0,
                            'max'=>200,
                            'steps'=>1,
                        ),
                        'desc_margin' => array(
                            'name'=>esc_html__( 'Margins (top right bottom left)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_margin']) ? '0px 0px 0px 0px' : $attributes['desc_margin']),
                            'placeholder' => '0px 0px 0px 0px'
                        ),
                    ),
                ),

                // @since 1.9
                'advanced' => array(
                    'name' => esc_html__( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,
                    ),
                ),

                'conditional_logic' => $conditional_logic_array
            )
        ),

        'html_predefined' => array(
            'name' => esc_html__( 'HTML', 'super-forms' ),
            'icon' => 'file-code;far',
            'predefined' => array(
                array(
                    'tag' => 'html',
                    'group' => 'html_elements',
                    'data' => array(
                        'html' => esc_html__( 'Your HTML here...', 'super-forms' )
                    )
                )
            ),
        ),
        'html' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::html',
            'name' => esc_html__( 'HTML', 'super-forms' ),
            'icon' => 'file-code;far',
            'atts' => array(
                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'title' => array(
                            'name'=>esc_html__( 'Title', 'super-forms' ),
                            'desc'=>'('.esc_html__( 'optional', 'super-forms' ).')',
                            'default'=> ( !isset( $attributes['title']) ? '' : $attributes['title']),
                            'i18n' => true
                        ),
                        'subtitle' => array(
                            'name'=>esc_html__( 'Sub Title', 'super-forms' ),
                            'desc'=>'('.esc_html__( 'optional', 'super-forms' ).')',
                            'default'=> ( !isset( $attributes['subtitle']) ? '' : $attributes['subtitle']),
                            'i18n' => true
                        ),
                        'html' => array(
                            'name'=>esc_html__( 'HTML', 'super-forms' ),
                            'type'=>'textarea',
                            'default'=> ( !isset( $attributes['html']) ? '' : $attributes['html']),
                            'i18n' => true
                        ),
                        
                        // @since 4.2.0 - automatically convert linebreaks to <br />
                        'nl2br' => array(
                            'name' => esc_html__( 'Enable line breaks', 'super-forms' ),
                            'label' => esc_html__( 'This will convert line breaks automatically to [br /] tags', 'super-forms' ),
                            'default'=> ( !isset( $attributes['nl2br']) ? 'true' : $attributes['nl2br']),
                            'type' => 'checkbox',
                            'values' => array(
                                'true' => esc_html__( 'Automatically add line breaks (enabled by default)', 'super-forms' ),
                            ),
                            'allow_empty' => true,
                        ),
                    ),
                ),

                // @since 1.9
                'advanced' => array(
                    'name' => esc_html__( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,
                    ),
                ),

                'conditional_logic' => $conditional_logic_array
            ),
        ),

        'divider_predefined' => array(
            'name' => esc_html__( 'Divider', 'super-forms' ),
            'icon' => 'minus',
            'predefined' => array(
                array(
                    'tag' => 'divider',
                    'group' => 'html_elements',
                    'data' => array()
                )
            ),
        ),
        'divider' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::divider',
            'name' => 'Divider',
            'icon' => 'minus',
            'atts' => array(
                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'color' => array(
                            'name'=>esc_html__( 'Divider color', 'super-forms' ),
                            'desc'=>esc_html__( 'Choose a custom border color.', 'super-forms' ),
                            'default'=> ( !isset( $attributes['color']) ? '#444444' : $attributes['color']),
                            'type'=>'color',
                        ),
                        'border' => array(
                            'name'=>esc_html__( 'Border style', 'super-forms' ),
                            'default'=> ( !isset( $attributes['border']) ? 'single' : $attributes['border']),
                            'type'=>'select', 
                            'filter'=>true,
                            'values'=>array(
                                'single' => esc_html__( 'Single', 'super-forms' ),
                                'double' => esc_html__( 'Double', 'super-forms' ),
                            ),
                        ),
                        'thickness' => array(
                            'name'=>esc_html__( 'Border thickness', 'super-forms' ),
                            'default'=> ( !isset( $attributes['thickness']) ? 1 : $attributes['thickness']),
                            'type'=>'slider',
                            'min'=>1,
                            'max'=>20,
                            'steps'=>1,
                        ),
                        'height' => array(
                            'name'=>esc_html__( 'Divider height', 'super-forms' ),
                            'default'=> ( !isset( $attributes['height']) ? 1 : $attributes['height']),
                            'type'=>'slider',
                            'min'=>1,
                            'max'=>20,
                            'steps'=>1,
                        ),
                        'border_style' => array(
                            'name'=>esc_html__( 'Border style', 'super-forms' ),
                            'default'=> ( !isset( $attributes['border_style']) ? 'dashed' : $attributes['border_style']),
                            'type'=>'select', 
                            'values'=>array(
                                'solid' => esc_html__( 'Solid', 'super-forms' ),
                                'dotted' => esc_html__( 'Dotted', 'super-forms' ),
                                'dashed' => esc_html__( 'Dashed', 'super-forms' ),
                            ),
                        ),
                        'width' => array(
                            'name'=>esc_html__( 'Divider weight', 'super-forms' ),
                            'desc'=>esc_html__( 'Define the width for the divider.', 'super-forms' ),
                            'default'=> ( !isset( $attributes['width']) ? '100' : $attributes['width']),
                            'type'=>'select', 
                            'filter'=>true,
                            'values'=>array(
                                '100' => '100% '.esc_html__( 'width', 'super-forms' ),
                                '75' => '75% '.esc_html__( 'width', 'super-forms' ),
                                '50' => '50% '.esc_html__( 'width', 'super-forms' ),
                                '25' => '25% '.esc_html__( 'width', 'super-forms' ),
                                'custom' => esc_html__( 'Custom width in pixels', 'super-forms' ),
                            )
                        ),
                        'custom_width' => array(
                            'name'=>esc_html__( 'Divider custom width', 'super-forms' ),
                            'desc'=>esc_html__( 'Define a custom width for the divider. Use a pixel value. eg: 150px', 'super-forms' ),
                            'default'=> ( !isset( $attributes['custom_width']) ? '150px' : $attributes['custom_width']),
                            'type'=>'text', 
                            'parent'=>'width',
                            'filter_value'=>'custom',
                            'filter'=>true,
                        ),
                        'align' => array(
                            'name'=>esc_html__( 'Divider alignment', 'super-forms' ),
                            'default'=> ( !isset( $attributes['align']) ? 'left' : $attributes['align']),
                            'type'=>'select', 
                            'values'=>array(
                                'left' => esc_html__( 'Align Left', 'super-forms' ),
                                'center' => esc_html__( 'Align Center', 'super-forms' ),
                                'right' => esc_html__( 'Align Right', 'super-forms' ),
                            ),
                        ),
                        'back' => array(
                            'name'=>esc_html__( 'Back to top button', 'super-forms' ),
                            'default'=> ( !isset( $attributes['back']) ? '0' : $attributes['back']),
                            'type'=>'select', 
                            'values'=>array(
                                '0' => esc_html__( 'Hide back to top button', 'super-forms' ),
                                '1' => esc_html__( 'Show back to top button', 'super-forms' ),
                            ),
                        ),
                    ),
                ),

                // @since 1.9
                'advanced' => array(
                    'name' => esc_html__( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,
                    ),
                ),

                'padding' => array(
                    'name' => esc_html__( 'Padding', 'super-forms' ),
                    'fields' => array(
                        'padding_top' => array(
                            'name'=>esc_html__( 'Padding top', 'super-forms' ),
                            'default'=> ( !isset( $attributes['padding_top']) ? 20 : $attributes['padding_top']),
                            'type'=>'slider',
                            'min'=>0,
                            'max'=>100,
                            'steps'=>5,
                        ),
                        'padding_bottom' => array(
                            'name'=>esc_html__( 'Padding bottom', 'super-forms' ),
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

        'spacer_predefined' => array(
            'name' => esc_html__( 'Spacer', 'super-forms' ),
            'icon' => 'arrows-alt-v',
            'predefined' => array(
                array(
                    'tag' => 'spacer',
                    'group' => 'html_elements',
                    'data' => array()
                )
            ),
        ),
        'spacer' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::spacer',
            'name' => 'Spacer',
            'icon' => 'arrows-alt-v',
            'atts' => array(
                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
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
                    'name' => esc_html__( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'class' => $class,
                        'wrapper_class' => $wrapper_class,
                    ),
                ),

                'conditional_logic' => $conditional_logic_array
            ),
        ),

        // @since 3.5.0 - google map element with API options
        'google_map_predefined' => array(
            'name' => esc_html__( 'Google Map', 'super-forms' ),
            'icon' => 'map',
            'predefined' => array(
                array(
                    'tag' => 'google_map',
                    'group' => 'html_elements',
                    'data' => array(
                        'polyline_geodesic' => 'true'
                    )
                )
            ),
        ),
        'google_map' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::google_map',
            'name' => 'Google Map',
            'icon' => 'map',
            'atts' => array(
                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'api_key' => array(
                            'name' => esc_html__( 'Google API key', 'super-forms' ), 
                            'label' => sprintf( esc_html__( 'In order to make calls you have to enable the following library in your %sAPI manager%s:%s- Google Maps JavaScript API', 'super-forms' ), '<a target="_blank" href="https://console.developers.google.com">', '</a>', '<br />' ),
                            'desc' => esc_html__( 'Required to do API calls to retrieve data', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['api_key'] ) ? '' : $attributes['api_key'] ),
                        ),
                        'address' => array(
                            'name' => esc_html__( 'Address (leave blank for none)', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['address'] ) ? '' : $attributes['address'] ),
                            'type' => 'text', 
                        ),
                        'address_marker' => array(
                            'desc' => esc_html__( 'This will add a marker on the address location on the map', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['address_marker'] ) ? '' : $attributes['address_marker'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => esc_html__( 'Add marker on address location', 'super-forms' ),
                            )
                        ),
                        'zoom' => array(
                            'name' => esc_html__( 'Map zoom', 'super-forms' ),
                            'label' => esc_html__( 'Higher means more zooming in', 'super-forms' ),
                            'default' => ( !isset( $attributes['zoom']) ? 5 : $attributes['zoom']),
                            'type' => 'slider', 
                            'min' => 1,
                            'max' => 20,
                            'steps' => 1,
                        ),
                        // Polylines
                        'enable_polyline' => array(
                            'name' => esc_html__( 'Add Polylines to the map', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['enable_polyline'] ) ? '' : $attributes['enable_polyline'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => esc_html__( 'Wether or not to draw Polyline on the map', 'super-forms' ),
                            )
                        ),
                        'polylines' => array(
                            'name' => esc_html__( 'Enter latitudes and longitudes', 'super-forms' ), 
                            'desc' => esc_html__( 'Wether or not to draw Polyline(s)', 'super-forms' ), 
                            'label' => sprintf( esc_html__( 'Use {tags} if needed%sPut each latitude and longitude on a new line seperated by pipes e.g: lat|lng', 'super-forms' ), '<br />' ), 
                            'placeholder' => "37.772|-122.214\n21.291|-157.821", 
                            'default'=> ( !isset( $attributes['polylines'] ) ? '' : $attributes['polylines'] ),
                            'type' => 'textarea', 
                            'filter'=>true,
                            'parent'=>'enable_polyline',
                            'filter_value'=>'true'
                        ),                        
                        // strokeWeight
                        'polyline_stroke_weight' => array(
                            'name' => esc_html__( 'Stroke weight', 'super-forms' ), 
                            'desc' => esc_html__( 'specifies the width of the line in pixels.', 'super-forms' ), 
                            'default' => ( !isset( $attributes['polyline_stroke_weight']) ? 2 : $attributes['polyline_stroke_weight']),
                            'type' => 'slider', 
                            'min' => 1,
                            'max' => 20,
                            'steps' => 1,
                            'filter'=>true,
                            'parent'=>'enable_polyline',
                            'filter_value'=>'true'
                        ),
                        // strokeColor
                        'polyline_stroke_color' => array(
                            'name' => esc_html__( 'Stroke color', 'super-forms' ), 
                            'desc' => esc_html__( 'Specifies the color of the line', 'super-forms' ), 
                            'default' => ( !isset( $attributes['polyline_stroke_color']) ? '#FF0000' : $attributes['polyline_stroke_color']),
                            'type' => 'color', 
                            'filter'=>true,
                            'parent'=>'enable_polyline',
                            'filter_value'=>'true'
                        ),
                        // strokeOpacity
                        'polyline_stroke_opacity' => array(
                            'name' => esc_html__( 'Stroke opacity', 'super-forms' ), 
                            'desc' => esc_html__( 'Specifies a numerical value between 0.0 and 1.0 to determine the opacity of the line\'s color. The default is 1.0.', 'super-forms' ), 
                            'default' => ( !isset( $attributes['polyline_stroke_opacity']) ? 1.0 : $attributes['polyline_stroke_opacity']),
                            'type' => 'slider', 
                            'min' => 0,
                            'max' => 1,
                            'steps' => 0.1,
                            'filter'=>true,
                            'parent'=>'enable_polyline',
                            'filter_value'=>'true'
                        ),
                        // Geodisc Polygon
                        'polyline_geodesic' => array(
                            'desc' => esc_html__( 'In a geodesic polyline, the segments of the polyline are drawn as the shortest path between two points on the Earth\'s surface, assuming the Earth is a sphere, as opposed to straight lines on the Mercator projection.', 'super-forms' ),
                            'label' => esc_html__( 'A geodesic polygon will retain its true geographic shape when it is moved, causing the polygon to appear distorted as it is moved north or south in the Mercator projection. Non-geodesic polygons will always retain their initial appearance on the screen.', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['polyline_geodesic'] ) ? '' : $attributes['polyline_geodesic'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => esc_html__( 'Enable Geodisc Polygon (default=enabled)', 'super-forms' ),
                            )
                        ),
                        'min_width' => array(
                            'name' => esc_html__( 'Min width in pixels', 'super-forms' ),
                            'label' => esc_html__( '0 = 500px min width', 'super-forms' ),
                            'default' => ( !isset( $attributes['height']) ? 500 : $attributes['height']),
                            'type' => 'slider', 
                            'min' => 0, 
                            'max' => 1000, 
                            'steps' => 10,
                        ),
                        'min_height' => array(
                            'name' => esc_html__( 'Min height in pixels', 'super-forms' ), 
                            'label' => esc_html__( '0 = 350px min height', 'super-forms' ),
                            'default' => ( !isset( $attributes['height']) ? 350 : $attributes['height']),
                            'type' => 'slider', 
                            'min' => 0, 
                            'max' => 1000, 
                            'steps' => 10,
                        ),
                        'max_width' => array(
                            'name' => esc_html__( 'Max width in pixels', 'super-forms' ),
                            'label' => esc_html__( 'Enter 0 for 100% max width', 'super-forms' ),
                            'default' => ( !isset( $attributes['height']) ? 0 : $attributes['height']),
                            'type' => 'slider', 
                            'min' => 0, 
                            'max' => 1000, 
                            'steps' => 10,
                        ),
                        'max_height' => array(
                            'name' => esc_html__( 'Max height in pixels', 'super-forms' ),
                            'label' => esc_html__( 'Enter 0 for 100% max height', 'super-forms' ),
                            'default' => ( !isset( $attributes['height']) ? 0 : $attributes['height']),
                            'type' => 'slider', 
                            'min' => 0, 
                            'max' => 1000, 
                            'steps' => 10,
                        ),
                    ),
                ),
            ),
        ),

    )
);