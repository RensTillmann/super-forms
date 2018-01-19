<?php
$array['html_elements'] = array(
    'title' => __( 'HTML Elements', 'super-forms' ),   
    'class' => 'super-html-elements',
    'shortcodes' => array(

        'image_predefined' => array(
            'name' => __( 'Image', 'super-forms' ),
            'icon' => 'picture-o',
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
                            'default'=> ( !isset( $attributes['alignment']) ? '' : $attributes['alignment']),
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

        'predefined_heading' => array(
            'name' => __( 'Heading', 'super-forms' ),
            'icon' => 'header',
            'predefined' => array(
                array(
                    'tag' => 'heading',
                    'group' => 'html_elements',
                    'data' => array(
                        'title' => __( 'Title', 'super-forms' )
                    )
                )            
            ),
            'atts' => array(),
        ),
        'heading' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::heading',
            'name' => __( 'Heading', 'super-forms' ),
            'icon' => 'header',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'title' => array(
                            'name' =>__( 'Title', 'super-forms' ),
                            'default' => ( !isset( $attributes['title']) ? '' : $attributes['title'])
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

        'html_predefined' => array(
            'name' => __( 'HTML', 'super-forms' ),
            'icon' => 'file-code-o',
            'predefined' => array(
                array(
                    'tag' => 'html',
                    'group' => 'html_elements',
                    'data' => array(
                        'html' => __( 'Your HTML here...', 'super-forms' )
                    )
                )
            ),
        ),
        'html' => array(
            'hidden' => true,
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
                            'default'=> ( !isset( $attributes['html']) ? '' : $attributes['html']),
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

        'divider_predefined' => array(
            'name' => __( 'Divider', 'super-forms' ),
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

        'spacer_predefined' => array(
            'name' => __( 'Spacer', 'super-forms' ),
            'icon' => 'arrows-v',
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

        // @since 3.5.0 - google map element with API options
        'google_map_predefined' => array(
            'name' => __( 'Google Map', 'super-forms' ),
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
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'api_key' => array(
                            'name' => __( 'Google API key', 'super-forms' ), 
                            'label' => __( 'In order to make calls you have to enable the following library in your <a target="_blank" href="https://console.developers.google.com">API manager</a>:<br />- Google Maps JavaScript API', 'super-forms' ),
                            'desc' => __( 'Required to do API calls to retrieve data', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['api_key'] ) ? '' : $attributes['api_key'] ),
                        ),
                        'address' => array(
                            'name' => __( 'Address (leave blank for none)', 'super-forms' ), 
                            'label' => __( 'Use {tags} if needed', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['address'] ) ? '' : $attributes['address'] ),
                            'type' => 'text', 
                        ),
                        'address_marker' => array(
                            'desc' => __( 'This will add a marker on the address location on the map', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['address_marker'] ) ? '' : $attributes['address_marker'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Add marker on address location', 'super-forms' ),
                            )
                        ),
                        'zoom' => array(
                            'name' => __( 'Map zoom', 'super-forms' ),
                            'label' => __( 'Higher means more zooming in', 'super-forms' ),
                            'default' => ( !isset( $attributes['zoom']) ? 5 : $attributes['zoom']),
                            'type' => 'slider', 
                            'min' => 1,
                            'max' => 20,
                            'steps' => 1,
                        ),
                        // Polylines
                        'enable_polyline' => array(
                            'name' => __( 'Add Polylines to the map', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['enable_polyline'] ) ? '' : $attributes['enable_polyline'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Wether or not to draw Polyline on the map', 'super-forms' ),
                            )
                        ),
                        'polylines' => array(
                            'name' => __( 'Enter latitudes and longitudes', 'super-forms' ), 
                            'desc' => __( 'Wether or not to draw Polyline(s)', 'super-forms' ), 
                            'label' => __( 'Use {tags} if needed<br />Put each latitude and longitude on a new line seperated by pipes e.g: lat|lng', 'super-forms' ), 
                            'placeholder' => "37.772|-122.214\n21.291|-157.821", 
                            'default'=> ( !isset( $attributes['polylines'] ) ? '' : $attributes['polylines'] ),
                            'type' => 'textarea', 
                            'filter'=>true,
                            'parent'=>'enable_polyline',
                            'filter_value'=>'true'
                        ),                        
                        // strokeWeight
                        'polyline_stroke_weight' => array(
                            'name' => __( 'Stroke weight', 'super-forms' ), 
                            'desc' => __( 'specifies the width of the line in pixels.', 'super-forms' ), 
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
                            'name' => __( 'Stroke color', 'super-forms' ), 
                            'desc' => __( 'Specifies the color of the line', 'super-forms' ), 
                            'default' => ( !isset( $attributes['polyline_stroke_color']) ? '#FF0000' : $attributes['polyline_stroke_color']),
                            'type' => 'color', 
                            'filter'=>true,
                            'parent'=>'enable_polyline',
                            'filter_value'=>'true'
                        ),
                        // strokeOpacity
                        'polyline_stroke_opacity' => array(
                            'name' => __( 'Stroke opacity', 'super-forms' ), 
                            'desc' => __( 'Specifies a numerical value between 0.0 and 1.0 to determine the opacity of the line\'s color. The default is 1.0.', 'super-forms' ), 
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
                            'desc' => __( 'In a geodesic polyline, the segments of the polyline are drawn as the shortest path between two points on the Earth\'s surface, assuming the Earth is a sphere, as opposed to straight lines on the Mercator projection.', 'super-forms' ),
                            'label' => __( 'A geodesic polygon will retain its true geographic shape when it is moved, causing the polygon to appear distorted as it is moved north or south in the Mercator projection. Non-geodesic polygons will always retain their initial appearance on the screen.', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['polyline_geodesic'] ) ? '' : $attributes['polyline_geodesic'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Enable Geodisc Polygon (default=enabled)', 'super-forms' ),
                            )
                        ),
                        'min_width' => array(
                            'name' => __( 'Min width in pixels', 'super-forms' ),
                            'label' => __( '0 = 500px min width', 'super-forms' ),
                            'default' => ( !isset( $attributes['height']) ? 500 : $attributes['height']),
                            'type' => 'slider', 
                            'min' => 0, 
                            'max' => 1000, 
                            'steps' => 10,
                        ),
                        'min_height' => array(
                            'name' => __( 'Min height in pixels', 'super-forms' ), 
                            'label' => __( '0 = 350px min height', 'super-forms' ),
                            'default' => ( !isset( $attributes['height']) ? 350 : $attributes['height']),
                            'type' => 'slider', 
                            'min' => 0, 
                            'max' => 1000, 
                            'steps' => 10,
                        ),
                        'max_width' => array(
                            'name' => __( 'Max width in pixels', 'super-forms' ),
                            'label' => __( 'Enter 0 for 100% max width', 'super-forms' ),
                            'default' => ( !isset( $attributes['height']) ? 0 : $attributes['height']),
                            'type' => 'slider', 
                            'min' => 0, 
                            'max' => 1000, 
                            'steps' => 10,
                        ),
                        'max_height' => array(
                            'name' => __( 'Max height in pixels', 'super-forms' ),
                            'label' => __( 'Enter 0 for 100% max height', 'super-forms' ),
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