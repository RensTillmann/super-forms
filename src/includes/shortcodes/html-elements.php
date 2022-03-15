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
                            'label'=>esc_html__( '(leave blank to use your WordPress theme styles)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_color']) ? '' : $attributes['heading_color']),
                            'type'=>'color',
                        ),
                        'heading_size' => array(
                            'name'=>esc_html__( 'Font size in pixels', 'super-forms' ),
                            'label'=>esc_html__( '(set to -1 to use your WordPress theme styles)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_size']) ? '-1' : $attributes['heading_size']),
                            'type'=>'slider',
                            'min'=>-1,
                            'max'=>200,
                            'steps'=>1,
                        ),
                        'heading_weight' => array(
                            'name'=>esc_html__( 'Font weight', 'super-forms' ),
                            'label'=>esc_html__( '(set to none to use your WordPress theme styles)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_weight']) ? 'none' : $attributes['heading_weight']),
                            'type'=>'select', 
                            'values'=>array(
                                'none' => 'None (default)',
                                '100' => '100',
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
                            'label'=>esc_html__( '(set to none to use your WordPress theme styles)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_align']) ? 'none' : $attributes['heading_align']),
                            'type'=>'select', 
                            'values'=>array(
                                'none' => esc_html__( 'None (default)' ,'super-forms' ),
                                'left' => esc_html__( 'Left' ,'super-forms' ),
                                'center' => esc_html__( 'Center' ,'super-forms' ),
                                'right' => esc_html__( 'Right' ,'super-forms' ),
                            ),       
                        ),
                        'heading_line_height' => array(
                            'name'=>esc_html__( 'Line height in pixels', 'super-forms' ),
                            'label'=>esc_html__( '(set to -1 to use your WordPress theme styles, set to 0 to use normal line height)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_line_height']) ? '-1' : $attributes['heading_line_height']),
                            'type'=>'slider',
                            'min'=>-1,
                            'max'=>200,
                            'steps'=>1,
                        ),
                        'heading_margin' => array(
                            'name'=>esc_html__( 'Margins (top right bottom left)', 'super-forms' ),
                            'label'=>esc_html__( '(leave blank to use your WordPress theme styles)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['heading_margin']) ? '' : $attributes['heading_margin']),
                            'placeholder' => '0px 0px 0px 0px'
                        ),
                    ),
                ),
                'desc_styles' => array(
                    'name' => esc_html__( 'Description Styles', 'super-forms' ),
                    'fields' => array(
                        'desc_color' => array(
                            'name'=>esc_html__( 'Font color', 'super-forms' ),
                            'label'=>esc_html__( '(leave blank to use your WordPress theme styles)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_color']) ? '' : $attributes['desc_color']),
                            'type'=>'color',
                        ),
                        'desc_size' => array(
                            'name'=>esc_html__( 'Font size in pixels', 'super-forms' ),
                            'label'=>esc_html__( '(set to -1 to use your WordPress theme styles)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_size']) ? '-1' : $attributes['desc_size']),
                            'type'=>'slider',
                            'min'=>-1,
                            'max'=>200,
                            'steps'=>1,
                        ),
                        'desc_weight' => array(
                            'name'=>esc_html__( 'Font weight', 'super-forms' ),
                            'label'=>esc_html__( '(set to none to use your WordPress theme styles)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_weight']) ? 'none' : $attributes['desc_weight']),
                            'type'=>'select', 
                            'values'=>array(
                                'none' => 'None (default)',
                                '100' => '100',
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
                            'label'=>esc_html__( '(set to none to use your WordPress theme styles)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_align']) ? 'none' : $attributes['desc_align']),
                            'type'=>'select', 
                            'values'=>array(
                                'none' => esc_html__( 'None (default)' ,'super-forms' ),
                                'left' => esc_html__( 'Left' ,'super-forms' ),
                                'center' => esc_html__( 'Center' ,'super-forms' ),
                                'right' => esc_html__( 'Right' ,'super-forms' ),
                            ),       
                        ),
                        'desc_line_height' => array(
                            'name'=>esc_html__( 'Line height in pixels', 'super-forms' ),
                            'label'=>esc_html__( '(set to -1 to use your WordPress theme styles, set to 0 to use normal line height)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_line_height']) ? '-1' : $attributes['desc_line_height']),
                            'type'=>'slider',
                            'min'=>-1,
                            'max'=>200,
                            'steps'=>1,
                        ),
                        'desc_margin' => array(
                            'name'=>esc_html__( 'Margins (top right bottom left)', 'super-forms' ),
                            'label'=>esc_html__( '(leave blank to use your WordPress theme styles)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['desc_margin']) ? '' : $attributes['desc_margin']),
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
            'name' => esc_html__( 'HTML (raw)', 'super-forms' ),
            'icon' => 'file-code;far',
            'predefined' => array(
                array(
                    'tag' => 'html',
                    'group' => 'html_elements',
                    'data' => array(
                        'name' => esc_html__( 'html', 'super-forms' ),
                        'email' => esc_html__( 'HTML:', 'super-forms' ),
                        'html' => esc_html__( 'Your HTML here...', 'super-forms' )
                    )
                )
            ),
        ),
        'html' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::html',
            'name' => esc_html__( 'HTML (raw)', 'super-forms' ),
            'icon' => 'file-code;far',
            'atts' => array(
                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, '' ),
                        'email' => SUPER_Shortcodes::email($attributes, '' ),
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
                            'label'=>esc_html__( 'Use {tags} if needed, you can also use third party [shortcodes] if needed. But please note that if you are using {tags} in combination with [shortcodes] from third parties, this might cause problems with initialized DOM elements. This is because when using {tags} the HTML will be updated to reflect the value change of a field, which causes the HTML to be reloaded, losing any initialized DOM elements. Best practise would be to not mix {tags} and [shortcodes] in a single HTML element.', 'super-forms' ),
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
                        'exclude' => $exclude_for_html_element,
                        'exclude_entry' => $exclude_entry_for_html_element // @since 3.3.0 - exclude data from being saved into contact entry
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
        'tinymce_predefined' => array(
            'name' => esc_html__( 'TinyMCE', 'super-forms' ),
            'icon' => 'file-code;far',
            'predefined' => array(
                array(
                    'tag' => 'tinymce',
                    'group' => 'html_elements',
                    'data' => array(
                        'name' => esc_html__( 'html', 'super-forms' ),
                        'email' => esc_html__( 'HTML:', 'super-forms' ),
                        'html' => esc_html__( 'Your HTML here...', 'super-forms' )
                    )
                )
            ),
        ),
        'tinymce' => array(
            'hidden' => true,
            'callback' => 'SUPER_Shortcodes::html',
            'name' => esc_html__( 'TinyMCE', 'super-forms' ),
            'icon' => 'file-code;far',
            'atts' => array(
                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, '' ),
                        'email' => SUPER_Shortcodes::email($attributes, '' ),
                        'html' => array(
                            'name'=>esc_html__( 'HTML', 'super-forms' ),
                            'label'=>esc_html__( 'Use {tags} if needed, you can also use third party [shortcodes] if needed. But please note that if you are using {tags} in combination with [shortcodes] from third parties, this might cause problems with initialized DOM elements. This is because when using {tags} the HTML will be updated to reflect the value change of a field, which causes the HTML to be reloaded, losing any initialized DOM elements. Best practise would be to not mix {tags} and [shortcodes] in a single HTML element.', 'super-forms' ),
                            'type'=>'tinymce',
                            'default'=> ( !isset( $attributes['html']) ? '' : $attributes['html']),
                            'i18n' => true
                        ),
                        'exclude' => $exclude,
                        'exclude_entry' => $exclude_entry // @since 3.3.0 - exclude data from being saved into contact entry
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
                        // currently not in use? 'polyline_geodesic' => 'true'
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
                        'api_region' => array(
                            'name' => esc_html__( 'Region', 'super-forms' ),
                            'label' => esc_html__( 'The region code to use. This alters the map\'s behavior based on a given country or territory. The region parameter accepts Unicode region subtag identifiers which (generally) have a one-to-one mapping to country code Top-Level Domains (ccTLDs). Most Unicode region identifiers are identical to ISO 3166-1 codes, with some notable exceptions. For example, Great Britain\'s ccTLD is "uk" (corresponding to the domain .co.uk) while its region identifier is "GB".', 'super-forms' ),
                            'default'=> ( !isset( $attributes['api_region'] ) ? '' : $attributes['api_region'] ),
                        ),
                        'api_language' => array(
                            'name' => esc_html__( 'Language', 'super-forms' ),
                            'label' => sprintf( esc_html__( 'The language to use. This affects the names of controls, copyright notices, driving directions, and control labels, as well as the responses to service requests. List of supported language codes: %sSupported Languages%s', 'super-forms' ), '<a href="https://developers.google.com/maps/faq?hl=nl#languagesupport">', '</a>'),
                            'default'=> ( !isset( $attributes['api_language'] ) ? 'en' : $attributes['api_language'] ),
                        ),

                        // Address Marker location
                        'address' => array(
                            'name' => esc_html__( 'Map address (location)', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['address'] ) ? '' : $attributes['address'] ),
                            'type' => 'text', 
                        ),
                        'address_marker' => array(
                            'desc' => esc_html__( 'This will add a marker for the address location on the map', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['address_marker'] ) ? '' : $attributes['address_marker'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => esc_html__( 'Add marker on address (location)', 'super-forms' ),
                            )
                        ),
                        // Directions API (route)
                        'origin' => array(
                            'name' => esc_html__( 'Origin (specifies the start location from which to calculate directions)', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['origin'] ) ? '' : $attributes['origin'] ),
                            'type' => 'text', 
                        ),
                        'destination' => array(
                            'name' => esc_html__( 'Destination (specifies the end location to which to calculate directions)', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['destination'] ) ? '' : $attributes['destination'] ),
                            'type' => 'text', 
                        ),
                        'populateDistance' => array(
                            'name' => esc_html__( '(optional) Populate the following field with the total distance', 'super-forms' ), 
                            'label' => esc_html__( 'The result will be expressed in meters. Enter the unique field name e.g: total_distance', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['populateDistance'] ) ? '' : $attributes['populateDistance'] ),
                            'type' => 'text',
                        ),
                        'populateDuration' => array(
                            'name' => esc_html__( '(optional) Populate the following field with the total Travel time (duration)', 'super-forms' ), 
                            'label' => esc_html__( 'The result will be expressed in seconds. Enter the unique field name e.g: total_traveltime', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['populateDuration'] ) ? '' : $attributes['populateDuration'] ),
                            'type' => 'text', 
                        ),
                        'directionsPanel' => array(
                            'default'=> ( !isset( $attributes['directionsPanel'] ) ? '' : $attributes['directionsPanel'] ),
                            'type' => 'checkbox', 
                            'values' => array(
                                'true' => esc_html__( 'Show directions panel (list directions next to the map)', 'super-forms' ),
                            )
                        ),
                        'travelMode' => array(
                            'name' => esc_html__( 'Travel mode (specifies what mode of transport to use when calculating directions)', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed, valid modes are: DRIVING, BICYCLING, TRANSIT, WALKING', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['travelMode'] ) ? 'DRIVING' : $attributes['travelMode'] ),
                            'type' => 'text'
                        ),
                        'unitSystem' => array(
                            'name' => esc_html__( 'Specifies what unit system to use when displaying results', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed. Valid values are: METRIC or IMPERIAL', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['unitSystem'] ) ? 'METRIC' : $attributes['unitSystem'] ),
                            'type' => 'text'
                        ),

                        // Waypoints
                        'waypoints' => array(
                            'name' => esc_html__( '(optional) Waypoints alter a route by routing it through the specified location(s)', 'super-forms' ), 
                            'label' => esc_html__( "Use {tags} if needed. Put each waypoint on a new line. Formatted like so: {location}|{stopover}\nWhere 'location' is the Address or LatLng and 'stopover' is either 'true' or 'false', where 'true' indicates that the waypoint is a stop on the route, which has the effect of splitting the route into two routes. Example values:\nAddress 1, City, Country|false\nAddress 2, City2, Country|true\nAddress 3, City3, Country|false\nAddress 4, City4, Country|false\n", 'super-forms' ), 
                            'default'=> ( !isset( $attributes['waypoints'] ) ? '' : $attributes['waypoints'] ),
                            'type' => 'textarea'
                        ),
                        'optimizeWaypoints' => array(
                            'name' => esc_html__( '(optional) specifies that the route using the supplied waypoints may be optimized by rearranging the waypoints in a more efficient order', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed. Valid values are: true or false', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['optimizeWaypoints'] ) ? '' : $attributes['optimizeWaypoints'] ),
                            'type' => 'text'
                        ),
                        'provideRouteAlternatives' => array(
                            'name' => esc_html__( '(optional) when set to true specifies that the Directions service may provide more than one route alternative in the response', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed. Valid values are: true or false', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['provideRouteAlternatives'] ) ? '' : $attributes['provideRouteAlternatives'] ),
                            'type' => 'text'
                        ),
                        'avoidFerries' => array(
                            'name' => esc_html__( '(optional) when set to true indicates that the calculated route(s) should avoid ferries, if possible', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed. Valid values are: true or false', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['avoidFerries'] ) ? '' : $attributes['avoidFerries'] ),
                            'type' => 'text'
                        ),
                        'avoidHighways' => array(
                            'name' => esc_html__( '(optional) when set to true indicates that the calculated route(s) should avoid major highways, if possible', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed. Valid values are: true or false', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['avoidHighways'] ) ? '' : $attributes['avoidHighways'] ),
                            'type' => 'text'
                        ),
                        'avoidTolls' => array(
                            'name' => esc_html__( '(optional) when set to true indicates that the calculated route(s) should avoid toll roads, if possible', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed. Valid values are: true or false', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['avoidTolls'] ) ? '' : $attributes['avoidTolls'] ),
                            'type' => 'text'
                        ),
                        'region' => array(
                            'name' => esc_html__( '(optional) specifies the region code, specified as a ccTLD ("top-level domain") two-character value', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed. Example values are: nl (for Netherlands), es (for Spain), de (for Germany), uk (for Great Britain)', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['region'] ) ? '' : $attributes['region'] ),
                            'type' => 'text'
                        ),
                        // drivingOptions (only when travelMode is DRIVING)
                        'departureTime' => array(
                            'name' => esc_html__( '(optional) Specifies the desired time of departure', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['departureTime'] ) ? '' : $attributes['departureTime'] ),
                            'type' => 'text'
                        ),
                        'trafficModel' => array(
                            'name' => esc_html__( '(optional) Specifies the assumptions to use when calculating time in traffic', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed. Valid values are: bestguess, pessimistic or optimistic', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['trafficModel'] ) ? '' : $attributes['trafficModel'] ),
                            'type' => 'text'
                        ),
                        // transitOptions (only when travelMode is TRANSIT)
                        'arrivalTime' => array(
                            'name' => esc_html__( '(optional) Specifies the desired time of arrival', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['arrivalTime'] ) ? '' : $attributes['arrivalTime'] ),
                            'type' => 'text'
                        ),
                        'transitDepartureTime' => array(
                            'name' => esc_html__( '(optional) Specifies the desired time of departure', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['transitDepartureTime'] ) ? '' : $attributes['transitDepartureTime'] ),
                            'type' => 'text'
                        ),
                        'TransitMode' => array(
                            'name' => esc_html__( '(optional) Specifies a preferred mode of transit', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed, seperate each mode with comma. Valid values are: BUS,RAIL,SUBWAY,TRAIN,TRAM', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['TransitMode'] ) ? '' : $attributes['TransitMode'] ),
                            'type' => 'text'
                        ),
                        'routingPreference' => array(
                            'name' => esc_html__( '(optional) Specifies preferences for transit routes', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed. Valid values are: FEWER_TRANSFERS or LESS_WALKING', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['routingPreference'] ) ? '' : $attributes['routingPreference'] ),
                            'type' => 'text'
                        ),
                        'zoom' => array(
                            'name' => esc_html__( 'Map zoom', 'super-forms' ),
                            'label' => esc_html__( 'Use {tags} if needed. Must be a value between 1 and 20 (higher means more zooming in)', 'super-forms' ),
                            'default' => ( !isset( $attributes['zoom']) ? '5' : $attributes['zoom']),
                            'type' => 'text'
                        ),
                        'disableDefaultUI' => array(
                            'name' => esc_html__( 'Disable default UI', 'super-forms' ),
                            'label' => esc_html__( 'Use {tags} if needed. Valid values are: true or false. When enabled it will not show the default buttons on the map', 'super-forms' ),
                            'default' => ( !isset( $attributes['disableDefaultUI']) ? 'true' : $attributes['disableDefaultUI']),
                            'type' => 'text'
                        ),
                        'region' => array(
                            'name' => esc_html__( '(optional) specifies the region code, specified as a TLD ("top-level domain") two-character value', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed. Example values are: us (for USA), nl (for Netherlands), es (for Spain), de (for Germany), uk (for Great Britain)', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['region'] ) ? '' : $attributes['region'] ),
                            'type' => 'text'
                        ),
                        'unitSystem' => array(
                            'name' => esc_html__( 'Specifies what unit system to use when displaying results', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed. Valid values are: METRIC or IMPERIAL', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['unitSystem'] ) ? 'METRIC' : $attributes['unitSystem'] ),
                            'type' => 'text'
                        ),
                        // Directions API (rout)
                        // Directions API (route)
                        'origin' => array(
                            'name' => esc_html__( 'Origin (specifies the start location from which to calculate directions)', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['origin'] ) ? '' : $attributes['origin'] ),
                            'type' => 'text', 
                        ),
                        'destination' => array(
                            'name' => esc_html__( 'Destination (specifies the end location to which to calculate directions)', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['destination'] ) ? '' : $attributes['destination'] ),
                            'type' => 'text', 
                        ),
                        'populateDistance' => array(
                            'name' => esc_html__( '(optional) Populate the following field with the total distance', 'super-forms' ), 
                            'label' => esc_html__( 'The result will be expressed in meters. Enter the unique field name e.g: total_distance', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['populateDistance'] ) ? '' : $attributes['populateDistance'] ),
                            'type' => 'text',
                        ),
                        'populateDuration' => array(
                            'name' => esc_html__( '(optional) Populate the following field with the total Travel time (duration)', 'super-forms' ), 
                            'label' => esc_html__( 'The result will be expressed in seconds. Enter the unique field name e.g: total_traveltime', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['populateDuration'] ) ? '' : $attributes['populateDuration'] ),
                            'type' => 'text', 
                        ),
                        'directionsPanel' => array(
                            'default'=> ( !isset( $attributes['directionsPanel'] ) ? '' : $attributes['directionsPanel'] ),
                            'type' => 'checkbox', 
                            'values' => array(
                                'true' => esc_html__( 'Show directions panel (list directions next to the map)', 'super-forms' ),
                            )
                        ),
                        'travelMode' => array(
                            'name' => esc_html__( 'Travel mode (specifies what mode of transport to use when calculating directions)', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed, valid modes are: DRIVING, BICYCLING, TRANSIT, WALKING', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['travelMode'] ) ? 'DRIVING' : $attributes['travelMode'] ),
                            'type' => 'text'
                        ),
                        // Waypoints
                        'waypoints' => array(
                            'name' => esc_html__( '(optional) Waypoints alter a route by routing it through the specified location(s)', 'super-forms' ), 
                            'label' => esc_html__( "Use {tags} if needed. Please make sure to not include the `Origin` and `Destination` locations in your waypoints. The Put each waypoint on a new line. Formatted like so: {location}|{stopover}\nWhere 'location' is the Address or LatLng and 'stopover' is either 'true' or 'false', where 'true' indicates that the waypoint is a stop on the route, which has the effect of splitting the route into two routes. Example values:\nAddress 1, City, Country|false\nAddress 2, City2, Country|true\nAddress 3, City3, Country|false\nAddress 4, City4, Country|false\n", 'super-forms' ), 
                            'default'=> ( !isset( $attributes['waypoints'] ) ? '' : $attributes['waypoints'] ),
                            'type' => 'textarea'
                        ),
                        'optimizeWaypoints' => array(
                            'name' => esc_html__( '(optional) specifies that the route using the supplied waypoints may be optimized by rearranging the waypoints in a more efficient order', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed. Valid values are: true or false', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['optimizeWaypoints'] ) ? '' : $attributes['optimizeWaypoints'] ),
                            'type' => 'text'
                        ),
                        'provideRouteAlternatives' => array(
                            'name' => esc_html__( '(optional) when set to true specifies that the Directions service may provide more than one route alternative in the response', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed. Valid values are: true or false', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['provideRouteAlternatives'] ) ? '' : $attributes['provideRouteAlternatives'] ),
                            'type' => 'text'
                        ),
                        'avoidFerries' => array(
                            'name' => esc_html__( '(optional) when set to true indicates that the calculated route(s) should avoid ferries, if possible', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed. Valid values are: true or false', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['avoidFerries'] ) ? '' : $attributes['avoidFerries'] ),
                            'type' => 'text'
                        ),
                        'avoidHighways' => array(
                            'name' => esc_html__( '(optional) when set to true indicates that the calculated route(s) should avoid major highways, if possible', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed. Valid values are: true or false', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['avoidHighways'] ) ? '' : $attributes['avoidHighways'] ),
                            'type' => 'text'
                        ),
                        'avoidTolls' => array(
                            'name' => esc_html__( '(optional) when set to true indicates that the calculated route(s) should avoid toll roads, if possible', 'super-forms' ), 
                            'label' => esc_html__( 'Use {tags} if needed. Valid values are: true or false', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['avoidTolls'] ) ? '' : $attributes['avoidTolls'] ),
                            'type' => 'text'
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
                        'min_height' => array(
                            'name' => esc_html__( 'Min height in pixels', 'super-forms' ), 
                            'label' => esc_html__( '0 = 350px min height', 'super-forms' ),
                            'default' => ( !isset( $attributes['height']) ? 350 : $attributes['height']),
                            'type' => 'slider', 
                            'min' => 0, 
                            'max' => 1000, 
                            'steps' => 10,
                        ),
                    ),
                ),
            ),
        ),
        'pdf_page_break' => array(
            'callback' => 'SUPER_Shortcodes::pdf_page_break',
            'name' => 'PDF Page Break',
            'icon' => 'file',
            'atts' => array(
                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'orientation' => array(
                            'name'=>esc_html__( 'Page orientation for the next page', 'super-forms' ),
                            'label'=>esc_html__( 'What should be the orientation of the next page?', 'super-forms' ),
                            'default'=> ( !isset( $attributes['orientation']) ? '' : $attributes['orientation']),
                            'type'=>'select',
                            'values'=>array(
                                ''=>esc_html__( 'Keep current orientation (default)', 'super-forms' ),
                                'landscape'=>esc_html__( 'Landscape', 'super-forms' ),
                                'portrait'=>esc_html__( 'Portrait', 'super-forms' ),
                                'default'=>esc_html__( 'Change to the orientation defined under PDF settings', 'super-forms' )
                            )
                        ),
                    ),
                ),
            ),
        ),
    )
);
