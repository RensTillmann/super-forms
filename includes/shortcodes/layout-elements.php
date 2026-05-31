<?php
$array['layout_elements'] = array(
    'title' => esc_html__( 'Layout Elements', 'super-forms' ),   
    'class' => 'super-layout-elements',
    'info' => esc_html__( 'Use it as a starting point, but you can customize the columns', 'super-forms' ),
    'shortcodes' => array(
        'column_one_full' => array(
            'name' => esc_html__( 'Column', 'super-forms' ),
            'icon' => 'user',
            'predefined' => array(
                array(
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array(
                        'size' => '1/1'
                    )
                )            
            ),
            'atts' => array(),
            'html' => '<span>1/1</span>',
        ),
        'column_one_half' => array(
            'name' => esc_html__( 'Column', 'super-forms' ),
            'icon' => 'user',
            'predefined' => array(
                array(
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array(
                        'size' => '1/2'
                    )
                ),
                array(
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array(
                        'size' => '1/2'
                    )
                )           
            ),
            'atts' => array(),
            'html' => '<span>1/2</span><span>1/2</span>',
        ),
        'column_one_third' => array(
            'name' => esc_html__( 'Column', 'super-forms' ),
            'icon' => 'user',
            'predefined' => array(
                array(
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array(
                        'size' => '1/3'
                    )
                ),
                array(
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array(
                        'size' => '1/3'
                    )
                ),
                array(
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array(
                        'size' => '1/3'
                    )
                )
            ),
            'atts' => array(),
            'html' => '<span>1/3</span><span>1/3</span><span>1/3</span>',
        ),
        'dyanmic_column_pre' => array(
            'name' => esc_html__( 'Repeater column', 'super-forms' ),
            'icon' => 'user',
            'predefined' => array(
                array(
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array(
                        'size' => '1/1',
                        'duplicate' => 'enabled',
                        'label' => 'Dynamic Column'
                    )
                )            
            ),
            'atts' => array(),
            'html' => '<span>' . esc_html__( 'Dynamic', 'super-forms' ) . '/' . esc_html__( 'Repeater', 'super-forms' ) . '<br />' . esc_html__( 'column', 'super-forms' ) . '</span>',
        ),
        'column' => array(
            'callback' => 'SUPER_Shortcodes::column',
            'hidden' => true,
            'drop' => true,
            'content' => ((!isset($content) || ($content=='')) ? '' : $content),
            'content_hidden' => true,
            'name' => 'Column',
            'icon' => 'column-width',
            'atts' => array(

                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'size' => array(
                            'name' => esc_html__( 'Column size', 'super-forms' ),
                            'default' => (!isset($attributes['size']) ? '1/1' : $attributes['size']),
                            'type' => 'select',
                            'values' => array(
                                '1/1' => '1/1',
                                '1/2' => '1/2',
                                '1/3' => '1/3',
                                '1/4' => '1/4',
                                '1/5' => '1/5',
                                '2/3' => '2/3',
                                '2/5' => '2/5',
                                '3/4' => '3/4',
                                '3/5' => '3/5',                              
                                '4/5' => '4/5',
                            )
                        ),
                        'invisible' => array(
                            'name' => esc_html__( 'Make column invisible', 'super-forms' ),
                            'label' => esc_html__( 'Please note that elements inside a hidden column will still be submitted as data by default', 'super-forms' ),
                            'default' => (!isset($attributes['invisible']) ? '' : $attributes['invisible']),
                            'type' => 'select',
                            'values' => array(
                                '' => 'No',
                                'true' => 'Yes',
                            )
                        ),
                        'align_elements' => array(
                            'name' => esc_html__( 'Align inner elements', 'super-forms' ),
                            'default' => (!isset($attributes['align_elements']) ? '' : $attributes['align_elements']),
                            'type' => 'select',
                            'values' => array(
                                '' => esc_html__( 'Default', 'super-forms' ),
                                'center' => esc_html__( 'Center', 'super-forms' ),
                                'left' => esc_html__( 'Left', 'super-forms' ),
                                'right' => esc_html__( 'Right', 'super-forms' )
                            )
                        ),
                        'duplicate' => array(
                            'name' =>esc_html__( 'Enable Add More', 'super-forms' ),
                            'label' =>esc_html__( 'Let users duplicate the fields inside this column', 'super-forms' ),
                            'default' => ( !isset( $attributes['duplicate'] ) ? '' : $attributes['duplicate'] ),
                            'type' => 'select',
                            'values' =>array(
                                '' => 'Disabled',
                                'enabled' => 'Enabled (allows users to add dynamic fields)',
                            ),
                            'filter' =>true,
                        ),
                        'duplicate_limit' => array(
                            'name' => esc_html__( 'Limit for dynamic fields (0 = unlimited)', 'super-forms' ), 
                            'label' => esc_html__( 'The total of times a user can click the "+" icon', 'super-forms' ), 
                            'type' => 'slider', 
                            'default' => ( !isset( $attributes['duplicate_limit'] ) ? 0 : $attributes['duplicate_limit'] ),
                            'min' => 0,
                            'max' => 50,
                            'steps' => 1,
                            'filter' =>true,
                            'parent' => 'duplicate',
                            'filter_value' => 'enabled'
                        ),

                        // @since 1.3
                        'duplicate_dynamically' => array(
                            'default' => ( !isset( $attributes['duplicate_dynamically'] ) ? 'true' : $attributes['duplicate_dynamically'] ),
                            'type' => 'checkbox', 
                            'values' => array(
                                'true' => esc_html__( 'Update conditional logic, {tags} and variable fields dynamically', 'super-forms' ),
                            ),
                            'allow_empty' => true,
                            'filter' =>true,
                            'parent' => 'duplicate',
                            'filter_value' => 'enabled'                            
                        ),


                        'label' => array(
                            'name' => esc_html__( 'Column Label', 'super-forms' ),
                            'label' => esc_html__( 'This makes it easier to keep track of your sections when building forms', 'super-forms' ),
                            'default' => ( !isset( $attributes['label'] ) ? 'Column' : $attributes['label'] )
                        ),

                        // @since 1.9
                        'class' => array(
                            'name' => esc_html__( 'Custom class', 'super-forms' ),
                            'label' => '(' . esc_html__( 'Add a custom class to append extra styles', 'super-forms' ) . ')',
                            'default' => ( !isset( $attributes['class'] ) ? '' : $attributes['class'] ),
                            'type' => 'text',
                        )

                    )
                ),
                'advanced' => array(
                    'name' => esc_html__( 'Advanced', 'super-forms' ),
                    'fields' => array(

                        // @since 1.9
                        'bg_image' => array(
                            'name' =>esc_html__( 'Background image', 'super-forms' ),
                            'default' => ( !isset( $attributes['bg_image']) ? '' : $attributes['bg_image']),
                            'type' => 'image',
                        ),

                        // @since 1.3
                        'bg_color' => array(
                            'name' =>esc_html__( 'Background color', 'super-forms' ),
                            'default' => (!isset($attributes['bg_color']) ? '' : $attributes['bg_color']),
                            'type' => 'color',
                        ),

                        // @since 1.9
                        'bg_opacity' => array(
                            'name' =>esc_html__( 'Background color opacity', 'super-forms' ),
                            'type' => 'slider', 
                            'default' => ( !isset( $attributes['bg_opacity'] ) ? 1 : $attributes['bg_opacity'] ),
                            'min' => 0,
                            'max' => 1,
                            'steps' => 0.1,
                        ),

                        // @since 1.3
                        'enable_padding' => array(
                            'desc' => esc_html__( 'Use custom padding', 'super-forms' ), 
                            'default' => ( !isset( $attributes['enable_padding'] ) ? '' : $attributes['enable_padding'] ),
                            'type' => 'checkbox', 
                            'filter' =>true,
                            'values' => array(
                                'true' => esc_html__( 'Enable custom padding', 'super-forms' ),
                            )
                        ),
                        // @since 1.3
                        'padding' => array(
                            'name' => esc_html__( 'Column paddings example: 0px 0px 0px 0px', 'super-forms' ),
                            'label' => esc_html__( '(leave blank for no custom paddings)', 'super-forms' ),
                            'default' => ( !isset( $attributes['padding'] ) ? '' : $attributes['padding'] ),
                            'type' => 'text',
                            'filter' =>true,
                            'parent' => 'enable_padding',
                            'filter_value' => 'true'
                        ),

                        'margin' => array(
                            'name' =>esc_html__( 'Remove margin', 'super-forms' ),
                            'default' => (!isset($attributes['margin']) ? '' : $attributes['margin']),
                            'type' => 'select',
                            'values' =>array(
                                '' => 'No',
                                'no_margin' => 'Yes',
                            )
                        ),

                        // @since 1.9
                        'position' => array(
                            'name' =>esc_html__( 'Positioning method', 'super-forms' ),
                            'default' => (!isset($attributes['position']) ? '' : $attributes['position']),
                            'type' => 'select',
                            'values' =>array(
                                '' => esc_html__( 'Static (default)', 'super-forms' ),
                                'relative' => esc_html__( 'Relative', 'super-forms' ),
                                'absolute' => esc_html__( 'Absolute', 'super-forms' ),
                                'fixed' => esc_html__( 'Fixed (not recommended)', 'super-forms' ),
                            ),
                            'filter' =>true,
                        ),
                        'positioning' => array(
                            'name' =>esc_html__( 'Positioning method', 'super-forms' ),
                            'default' => (!isset($attributes['positioning']) ? '' : $attributes['positioning']),
                            'type' => 'select',
                            'values' =>array(
                                '' => esc_html__( 'None', 'super-forms' ),
                                'top_left' => esc_html__( 'Top and Left', 'super-forms' ),
                                'top_right' => esc_html__( 'Top and Right', 'super-forms' ),
                                'bottom_left' => esc_html__( 'Bottom and Left', 'super-forms' ),
                                'bottom_right' => esc_html__( 'Bottom and Right', 'super-forms' ),
                            ),
                            'filter' =>true,
                            'parent' => 'position',
                            'filter_value' => 'relative,absolute,fixed'
                        ),
                        'positioning_top' => array(
                            'name' => esc_html__( 'Positioning top e.g: 10px', 'super-forms' ),
                            'default' => ( !isset( $attributes['positioning_top'] ) ? '' : $attributes['positioning_top'] ),
                            'type' => 'text',
                            'filter' =>true,
                            'parent' => 'positioning',
                            'filter_value' => 'top_left,top_right'
                        ),
                        'positioning_right' => array(
                            'name' => esc_html__( 'Positioning right e.g: 10px', 'super-forms' ),
                            'default' => ( !isset( $attributes['positioning_right'] ) ? '' : $attributes['positioning_right'] ),
                            'type' => 'text',
                            'filter' =>true,
                            'parent' => 'positioning',
                            'filter_value' => 'top_right,bottom_right'
                        ),
                        'positioning_bottom' => array(
                            'name' => esc_html__( 'Positioning bottom e.g: 10px', 'super-forms' ),
                            'default' => ( !isset( $attributes['positioning_bottom'] ) ? '' : $attributes['positioning_bottom'] ),
                            'type' => 'text',
                            'filter' =>true,
                            'parent' => 'positioning',
                            'filter_value' => 'bottom_left,bottom_right'
                        ),
                        'positioning_left' => array(
                            'name' => esc_html__( 'Positioning left e.g: 10px', 'super-forms' ),
                            'default' => ( !isset( $attributes['positioning_left'] ) ? '' : $attributes['positioning_left'] ),
                            'type' => 'text',
                            'filter' =>true,
                            'parent' => 'positioning',
                            'filter_value' => 'top_left,bottom_left'
                        ),
                    )
                ),

                // @since 1.9
                'responsiveness' => array(
                    'name' => esc_html__( 'Responsiveness', 'super-forms' ),
                    'fields' => array(
                        'hide_on_mobile' => array(
                            'name' => esc_html__( 'Based on form width (breaking point = 760px)', 'super-forms' ),
                            'default' => ( !isset( $attributes['hide_on_mobile'] ) ? '' : $attributes['hide_on_mobile'] ),
                            'type' => 'checkbox', 
                            'filter' =>true,
                            'values' => array(
                                'true' => esc_html__( 'Hide on mobile devices', 'super-forms' ),
                            )
                        ),
                        'resize_disabled_mobile' => array(
                            'default' => ( !isset( $attributes['resize_disabled_mobile'] ) ? '' : $attributes['resize_disabled_mobile'] ),
                            'type' => 'checkbox', 
                            'filter' =>true,
                            'values' => array(
                                'true' => esc_html__( 'Keep original size on mobile devices (prevents 100% width)', 'super-forms' ),
                            )
                        ),
                        'hide_on_mobile_window' => array(
                            'name' => esc_html__( 'Based on screen width (breaking point = 760px)', 'super-forms' ),
                            'default' => ( !isset( $attributes['hide_on_mobile_window'] ) ? '' : $attributes['hide_on_mobile_window'] ),
                            'type' => 'checkbox', 
                            'filter' =>true,
                            'values' => array(
                                'true' => esc_html__( 'Hide on mobile devices', 'super-forms' ),
                            )
                        ),
                        'resize_disabled_mobile_window' => array(
                            'default' => ( !isset( $attributes['resize_disabled_mobile_window'] ) ? '' : $attributes['resize_disabled_mobile_window'] ),
                            'type' => 'checkbox', 
                            'filter' =>true,
                            'values' => array(
                                'true' => esc_html__( 'Keep original size on mobile devices (prevents 100% width)', 'super-forms' ),
                            )
                        ),
                        'force_responsiveness_mobile_window' => array(
                            'default' => ( !isset( $attributes['force_responsiveness_mobile_window'] ) ? '' : $attributes['force_responsiveness_mobile_window'] ),
                            'type' => 'checkbox', 
                            'filter' =>true,
                            'values' => array(
                                'true' => esc_html__( 'Force responsiveness on mobile devices (always 100% width)', 'super-forms' ),
                            )
                        ),

                    )
                ),

                'conditional_logic' => $conditional_logic_array
            )
        ),
        'multipart_pre' => array(
            'name' => esc_html__( 'Multi Part', 'super-forms' ),
            'icon' => 'user',
            'predefined' => array(
                array(
                    'tag' => 'multipart',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array()
                )            
            ),
            'atts' => array(),
            'html' => '<span>' . esc_html__( 'Multi Part', 'super-forms' ) . '/' . esc_html__( 'Step', 'super-forms' ) . '</span>',
        ),
        'multipart' => array(
            'callback' => 'SUPER_Shortcodes::multipart',
            'hidden' => true,
            'drop' => true,
            'content' => ((!isset($content) || ($content=='')) ? '' : $content),
            'content_hidden' => true,
            'name' => esc_html__( 'Multi Part', 'super-forms' ),
            'atts' => array(
                'multi_part' => array(
                    'name' => esc_html__( 'Multi Part', 'super-forms' ),
                    'fields' => array(
                        'auto' => array(
                            'name' =>esc_html__( 'Automatically go to next step', 'super-forms' ),
                            'desc' =>esc_html__( 'After last field is filled out, go to next step automatically', 'super-forms' ),
                            'default' => ( !isset( $attributes['auto'] ) ? 'no' : $attributes['auto'] ),
                            'type' => 'select',
                            'values' =>array(
                                'no' =>esc_html__( 'No (disabled)', 'super-forms' ),
                                'yes' =>esc_html__( 'Yes (enabled)', 'super-forms' )
                            )
                        ),
                        'autofocus' => array(
                            'desc' =>esc_html__( 'This will prevent the first element from being automatically focussed when this multi-part becomes active', 'super-forms' ),
                            'default' => ( !isset( $attributes['autofocus'] ) ? '' : $attributes['autofocus'] ),
                            'type' => 'checkbox',
                            'values' =>array(
                                'true' =>esc_html__( 'Disable autofocus on first field', 'super-forms' ),
                            )
                        ),
                        'validate' => array(
                            'desc' =>esc_html__( 'Prevent users from going to next step if it contains errors', 'super-forms' ),
                            'default' => ( !isset( $attributes['validate'] ) ? '' : $attributes['validate'] ),
                            'type' => 'checkbox',
                            'values' =>array(
                                'true' =>esc_html__( 'Check for errors before going to next step', 'super-forms' ),
                            )
                        ),

                        // @since 4.2.0 - disable scrolling when multi-part contains errors
                        'disable_scroll' => array(
                            'desc' =>esc_html__( 'This will prevent scrolling effect when an error was found for the current step', 'super-forms' ),
                            'default' => ( !isset( $attributes['disable_scroll'] ) ? '' : $attributes['disable_scroll'] ),
                            'type' => 'checkbox',
                            'values' =>array(
                                'true' =>esc_html__( 'Disable scrolling on error', 'super-forms' ),
                            )
                        ),
                    
                        // @since 4.3.0 - disable scrolling for multi-part next prev
                        'disable_scroll_pn' => array(
                            'desc' =>esc_html__( 'This will prevent scrolling effect when the Next or Prev button was clicked', 'super-forms' ),
                            'default' => ( !isset( $attributes['disable_scroll_pn'] ) ? '' : $attributes['disable_scroll_pn'] ),
                            'type' => 'checkbox',
                            'values' =>array(
                                'true' =>esc_html__( 'Disable scrolling on Prev and Next button click', 'super-forms' ),
                            )
                        ),



                        'prev_text' => array(
                            'name' =>esc_html__( 'Previous button text', 'super-forms' ),
                            'default' => (!isset($attributes['prev_text']) ? esc_html__( 'Prev', 'super-forms' )  : $attributes['prev_text']),
                            'type' => 'text',
                            'i18n' => true
                        ),
                        'next_text' => array(
                            'name' =>esc_html__( 'Next button text', 'super-forms' ),
                            'default' => (!isset($attributes['next_text']) ? esc_html__( 'Next', 'super-forms' )  : $attributes['next_text']),
                            'type' => 'text',
                            'i18n' => true
                        ),
                        
                        // @since 1.9
                        'class' => array(
                            'name' => esc_html__( 'Custom class', 'super-forms' ),
                            'desc' => '(' . esc_html__( 'Add a custom class to append extra styles', 'super-forms' ) . ')',
                            'default' => ( !isset( $attributes['class'] ) ? '' : $attributes['class'] ),
                            'type' => 'text',
                        ),
                        'step_image' => array(
                            'name'=>esc_html__( 'Step Image', 'super-forms' ),
                            'desc' =>esc_html__( 'Leave blank if you prefer to not use an icon.', 'super-forms' ),
                            'default'=> ( !isset( $attributes['step_image']) ? '' : $attributes['step_image']),
                            'type'=>'image'
                        ),
                        'step_name' => array(
                            'name' =>esc_html__( 'Step Name', 'super-forms' ),
                            'label' =>esc_html__( 'For developers only, not visible by default, you can change this with custom CSS.', 'super-forms' ),
                            'default' => (!isset($attributes['step_name']) ? esc_html__( 'Step 1', 'super-forms' )  : $attributes['step_name']),
                            'type' => 'text',
                            'i18n' => true 
                        ),
                        'step_description' => array(
                            'name' =>esc_html__( 'Step Description', 'super-forms' ),
                            'label' =>esc_html__( 'For developers only, not visible by default, you can change this with custom CSS.', 'super-forms' ),
                            'default' => (!isset($attributes['step_description']) ? esc_html__( 'Description for this step', 'super-forms' ) : $attributes['step_description']),
                            'type' => 'text',
                            'i18n' => true
                        ),
                        'show_icon' => array(
                            'default' => ( !isset( $attributes['show_icon'] ) ? '' : $attributes['show_icon'] ),
                            'type' => 'checkbox',
                            'values' =>array(
                                'true' =>esc_html__( 'Show an icon instead of number', 'super-forms' ),
                            ),
                            'filter' => true
                        ),
                        'icon' => array(
                            'name' =>esc_html__( 'Select an Icon', 'super-forms' ), 
                            'label' =>esc_html__( 'If you have set an image, the image will override the icon', 'super-forms' ),
                            'desc' =>esc_html__( 'Leave blank if you prefer to not use an icon.', 'super-forms' ),
                            'default' => (!isset($attributes['icon']) ? '' : $attributes['icon']),
                            'type' => 'icon',
                            'filter' => true,
                            'parent' => 'show_icon',
                            'filter_value' => 'true'
                        ),
                    )
                )
            )
        ),
        'tabs_pre' => array(
            'name' => esc_html__( 'Tabs', 'super-forms' ),
            'predefined' => array(
                array(
                    'tag' => 'tabs',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array(
                        'layout' => 'tabs',
                        'items' => array(
                            array(
                                'title' => esc_html__('Tab 1', 'super-forms'),
                                'desc' => esc_html__('Description', 'super-forms')
                            ),
                            array(
                                'title' => esc_html__('Tab 2', 'super-forms'),
                                'desc' => esc_html__('Description', 'super-forms')
                            ),
                            array(
                                'title' => esc_html__('Tab 3', 'super-forms'),
                                'desc' => esc_html__('Description', 'super-forms')
                            )
                        )
                    )
                )
            ),
            'atts' => array(),
            'html' => '<span>' . esc_html__( 'Tabs', 'super-forms' ) . '</span>'
        ),
        'accordion_pre' => array(
            'name' => esc_html__( 'Accordion', 'super-forms' ),
            'predefined' => array(
                array(
                    'tag' => 'tabs',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array(
                        'layout' => 'accordion',
                        'items' => array(
                            array(
                                'title' => esc_html__('Tab 1', 'super-forms'),
                                'desc' => esc_html__('Description', 'super-forms')
                            ),
                            array(
                                'title' => esc_html__('Tab 2', 'super-forms'),
                                'desc' => esc_html__('Description', 'super-forms')
                            ),
                            array(
                                'title' => esc_html__('Tab 3', 'super-forms'),
                                'desc' => esc_html__('Description', 'super-forms')
                            )
                        )
                    )
                )
            ),
            'atts' => array(),
            'html' => '<span>' . esc_html__( 'Accordion', 'super-forms' ) . '</span>'
        ),
        'tabs' => array(
            'callback' => 'SUPER_Shortcodes::tabs',
            'hidden' => true,
            'drop' => true,
            'name' => esc_html__( 'Tabs/Accordion', 'super-forms' ),
            'atts' => array(
                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'items' => array(
                            'type' => 'tab_items',
                            'default' => ( !isset( $attributes['dropdown_items'] ) ? 
                                array(
                                    array(
                                        'image' => '',
                                        'title' => esc_html__( 'Tab', 'super-forms' ) . ' 1',
                                        'desc' => esc_html__( 'Description', 'super-forms' )
                                    ),
                                    array(
                                        'image' => '',
                                        'title' => esc_html__( 'Tab', 'super-forms' ) . ' 2',
                                        'desc' => esc_html__( 'Description', 'super-forms' )
                                    ),
                                    array(
                                        'image' => '',
                                        'title' => esc_html__( 'Tab', 'super-forms' ) . ' 3',
                                        'desc' => esc_html__( 'Description', 'super-forms' )
                                    )
                                ) : $attributes['dropdown_items']
                            ),
                            'i18n' => true
                        ),
                        'layout' => array(
                            'name' => esc_html__( 'Layout', 'super-forms' ),
                            'default' => ( !isset( $attributes['layout'] ) ? 'tabs' : $attributes['layout'] ),
                            'type' => 'image_select',
                            'values' => array(
                                'tabs' => array(
                                    'title' => esc_html__( 'Tabs (default)', 'super-forms' ),
                                    'icon' => 'far fa-folder'
                                ),
                                'accordion' => array(
                                    'title' => esc_html__( 'Accordion', 'super-forms' ),
                                    'icon' => 'far fa-caret-square-down'
                                )
                            ),
                            'filter' => true
                        ),
                        'tab_location' => array(
                            'name' => esc_html__( 'Tab Location', 'super-forms' ),
                            'default' => ( !isset( $attributes['tab_location'] ) ? 'horizontal' : $attributes['tab_location'] ),
                            'type' => 'select',
                            'values' => array(
                                'horizontal' => esc_html__( 'Horizontal tabs', 'super-forms' ),
                                'vertical' => esc_html__( 'Vertical tabs', 'super-forms' ),
                            ),
                            'filter' => true,
                            'parent' => 'layout',
                            'filter_value' => 'tabs'
                        ),
                        'tab_show_prev_next' => array(
                            'default' => ( !isset( $attributes['tab_show_prev_next'] ) ? '' : $attributes['tab_show_prev_next'] ),
                            'type' => 'checkbox', 
                            'values' => array(
                                'true' => esc_html__( 'Display previous and next buttons', 'super-forms' ),
                            ),
                            'filter' =>true,
                            'parent' => 'tab_location',
                            'filter_value' => 'horizontal'                            
                        ),
                        'prev_next_color' => array(
                            'name' => esc_html__( 'Previous / Next color', 'super-forms' ),
                            'type' => 'color',
                            'default' => (!isset($attributes['prev_next_color']) ? '' : $attributes['prev_next_color']),
                            '_styles' => array(
                                ' > .super-tabs-contents > .super-content-prev i' => 'background',
                                ' > .super-tabs-contents > .super-content-next i' => 'background'
                            ),
                            'filter' =>true,
                            'parent' => 'tab_show_prev_next',
                            'filter_value' => 'true'   
                        ),
                        'tab_class' => array(
                            'name' => esc_html__( 'Custom TAB class', 'super-forms' ),
                            'desc' => '(' . esc_html__( 'Add a custom TAB class to append extra styles', 'super-forms' ) . ')',
                            'default' => ( !isset( $attributes['tab_class'] ) ? '' : $attributes['tab_class'] ),
                            'type' => 'text',
                        ),
                        'content_class' => array(
                            'name' => esc_html__( 'Custom Content class', 'super-forms' ),
                            'desc' => '(' . esc_html__( 'Add a custom Content class to append extra styles', 'super-forms' ) . ')',
                            'default' => ( !isset( $attributes['content_class'] ) ? '' : $attributes['content_class'] ),
                            'type' => 'text',
                        )
                    )
                ),


                // Theme styles
                'theme_styles' => array(
                    'name' => esc_html__( 'Theme styles', 'super-forms' ),
                    'default' => array(
                        'name' => esc_html__( 'Default' , 'super-forms' ),
                        'fields' => array(
                            // Content background color
                            // Content border color
                            // Content paddings
                            'content_bgcolor' => array(
                                'name' => esc_html__( 'Content background color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['content_bgcolor']) ? '' : $attributes['content_bgcolor']),
                                '_styles' => array(
                                    ' > .super-tabs-contents' => 'background-color',
                                    ' > .super-accordion-item > .super-accordion-content' => 'background-color',
                                    ' > .super-tabs-menu > .super-tabs-tab.super-active:after' => 'background-color'
                                ),
                            ),
                            // Tab background color
                            // Accordion Tab +/- icon color
                            // Tab padding
                            // Tab text align
                            'tab_bgcolor' => array(
                                'name' => esc_html__( 'Tab background color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['tab_bgcolor']) ? '' : $attributes['tab_bgcolor']),
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab' => 'background-color',
                                    ' > .super-accordion-item > .super-accordion-header' => 'background-color'
                                ),
                            ),
                            'tab_icon_color' => array(
                                'name' => esc_html__( 'Tab collaps icon color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['tab_icon_color']) ? '' : $attributes['tab_icon_color']),
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header:before' => 'background-color',
                                    ' > .super-accordion-item > .super-accordion-header:after' => 'background-color'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            'tab_textalign' => array(
                                'name' => esc_html__( 'Tab text align', 'super-forms' ),
                                'default' => ( !isset( $attributes['tab_textalign'] ) ? 'left' : $attributes['tab_textalign'] ),
                                'type' => 'select',
                                'values' => array(
                                    'left' => esc_html__( 'Left (default)', 'super-forms' ),
                                    'center' => esc_html__( 'Center', 'super-forms' ),
                                    'right' => esc_html__( 'Right', 'super-forms' )
                                ),
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab > *' => 'text-align',
                                    ' > .super-accordion-item > .super-accordion-header' => 'justify-content'
                                ),
                            ),
                            // Title color
                            'title_font_color' => array(
                                'name' => esc_html__( 'Title color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['title_font_color']) ? '' : $attributes['title_font_color']),
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab > .super-tab-title' => 'color',
                                    ' > .super-accordion-item > .super-accordion-header > .super-accordion-title' => 'color'
                                ),
                            ),
                            // Description color
                            'desc_font_color' => array(
                                'name' => esc_html__( 'Description color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['desc_font_color']) ? '' : $attributes['desc_font_color']),
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab > .super-tab-desc' => 'color',
                                    ' > .super-accordion-item > .super-accordion-header > .super-accordion-desc' => 'color'
                                ),
                            ),
                        )
                    ),
                    'hover' => array(
                        'name' => esc_html__( 'Hover' , 'super-forms' ),
                        'fields' => array(
                            // Tab background color
                            'tab_bgcolor_hover' => array(
                                'name' => esc_html__( 'Tab background color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['tab_bgcolor_hover']) ? '' : $attributes['tab_bgcolor_hover']),
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab:hover' => 'background-color',
                                    ' > .super-accordion-item:hover .super-accordion-header' => 'background-color'
                                )
                            ),
                            'tab_icon_color_hover' => array(
                                'name' => esc_html__( 'Tab collapse icon color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['tab_icon_color_hover']) ? '' : $attributes['tab_icon_color_hover']),
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header:hover:before' => 'background-color',
                                    ' > .super-accordion-item > .super-accordion-header:hover:after' => 'background-color'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            // Title color
                            'title_font_color_hover' => array(
                                'name' => esc_html__( 'Title color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['title_font_color_hover']) ? '' : $attributes['title_font_color_hover']),
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab:hover > .super-tab-title' => 'color',
                                    ' > .super-accordion-item > .super-accordion-header:hover > .super-accordion-title' => 'color'
                                ),
                            ),
                            // Description color
                            'desc_font_color_hover' => array(
                                'name' => esc_html__( 'Description color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['desc_font_color_hover']) ? '' : $attributes['desc_font_color_hover']),
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab:hover > .super-tab-desc' => 'color',
                                    ' > .super-accordion-item > .super-accordion-header:hover > .super-accordion-desc' => 'color'
                                ),
                            ),
                        )
                    ),
                    'active' => array(
                        'name' => esc_html__( 'Active' , 'super-forms' ),
                        'fields' => array(
                            // Tab background color
                            // Tab border color
                            'tab_bgcolor_active' => array(
                                'name' => esc_html__( 'Tab background color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['tab_bgcolor_active']) ? '' : $attributes['tab_bgcolor_active']),
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab.super-active' => 'background-color',
                                    ' > .super-accordion-item.super-active .super-accordion-header' => 'background-color'
                                )
                            ),
                            'tab_icon_color_active' => array(
                                'name' => esc_html__( 'Tab collapse icon color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['tab_icon_color_active']) ? '' : $attributes['tab_icon_color_active']),
                                '_styles' => array(
                                    ' > .super-accordion-item.super-active > .super-accordion-header:before' => 'background-color',
                                    ' > .super-accordion-item.super-active > .super-accordion-header:after' => 'background-color'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            // Title color
                            'title_font_color_active' => array(
                                'name' => esc_html__( 'Title color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['title_font_color_active']) ? '' : $attributes['title_font_color_active']),
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab.super-active > .super-tab-title' => 'color',
                                    ' > .super-accordion-item.super-active > .super-accordion-header > .super-accordion-title' => 'color'
                                ),
                            ),
                            // Description color
                            'desc_font_color_active' => array(
                                'name' => esc_html__( 'Description color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['desc_font_color_active']) ? '' : $attributes['desc_font_color_active']),
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab.super-active > .super-tab-desc' => 'color',
                                    ' > .super-accordion-item.super-active > .super-accordion-header > .super-accordion-desc' => 'color'
                                ),
                            ),
                        )
                    ),
                ),
                // Font styles
                'font_styles' => array(
                    'name' => esc_html__( 'Font styles', 'super-forms' ),
                    'default' => array(
                        'name' => esc_html__( 'Default' , 'super-forms' ),
                        'fields' => array(
                            // Title size
                            // Title line-height
                            // Title weight
                            'title_font_size' => array(
                                'name' => esc_html__( 'Title size in pixels (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['title_font_size'] ) ? 0 : $attributes['title_font_size'] ),
                                'min' => 0,
                                'max' => 50,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab > .super-tab-title' => 'font-size',
                                    ' > .super-accordion-item > .super-accordion-header > .super-accordion-title' => 'font-size'
                                )
                            ),
                            'title_font_lineheight' => array(
                                'name' => esc_html__( 'Title line-height in pixels (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['title_font_lineheight'] ) ? 0 : $attributes['title_font_lineheight'] ),
                                'min' => 0,
                                'max' => 50,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab > .super-tab-title' => 'line-height',
                                    ' > .super-accordion-item > .super-accordion-header > .super-accordion-title' => 'line-height'
                                )
                            ),
                            'title_font_weight' => array(
                                'name' => esc_html__( 'Title weight (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['title_font_weight'] ) ? 0 : $attributes['title_font_weight'] ),
                                'min' => 0,
                                'max' => 900,
                                'steps' => 100,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab > .super-tab-title' => 'font-weight',
                                    ' > .super-accordion-item > .super-accordion-header > .super-accordion-title' => 'font-weight'
                                )
                            ),
                            // Description size
                            // Description line-height
                            // Description weight
                            'desc_font_size' => array(
                                'name' => esc_html__( 'Description size in pixels (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['desc_font_size'] ) ? 0 : $attributes['desc_font_size'] ),
                                'min' => 0,
                                'max' => 50,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab > .super-tab-desc' => 'font-size',
                                    ' > .super-accordion-item > .super-accordion-header > .super-accordion-desc' => 'font-size'
                                )
                            ),
                            'desc_font_lineheight' => array(
                                'name' => esc_html__( 'Description line-height in pixels (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['desc_font_lineheight'] ) ? 0 : $attributes['desc_font_lineheight'] ),
                                'min' => 0,
                                'max' => 50,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab > .super-tab-desc' => 'line-height',
                                    ' > .super-accordion-item > .super-accordion-header > .super-accordion-desc' => 'line-height'
                                )
                            ),
                            'desc_font_weight' => array(
                                'name' => esc_html__( 'Description weight (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['desc_font_weight'] ) ? 0 : $attributes['desc_font_weight'] ),
                                'min' => 0,
                                'max' => 900,
                                'steps' => 100,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab > .super-tab-desc' => 'font-weight',
                                    ' > .super-accordion-item > .super-accordion-header > .super-accordion-desc' => 'font-weight'
                                )
                            ),

                        )
                    ),
                    'hover' => array(
                        'name' => esc_html__( 'Hover' , 'super-forms' ),
                        'fields' => array(
                            // Title size
                            // Title line-height
                            // Title weight
                            'title_font_size_hover' => array(
                                'name' => esc_html__( 'Title size in pixels (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['title_font_size_hover'] ) ? 0 : $attributes['title_font_size_hover'] ),
                                'min' => 0,
                                'max' => 50,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab:hover > .super-tab-title' => 'font-size',
                                    ' > .super-accordion-item > .super-accordion-header:hover > .super-accordion-title' => 'font-size'
                                )
                            ),
                            'title_font_lineheight_hover' => array(
                                'name' => esc_html__( 'Title line-height in pixels (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['title_font_lineheight_hover'] ) ? 0 : $attributes['title_font_lineheight_hover'] ),
                                'min' => 0,
                                'max' => 50,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab:hover > .super-tab-title' => 'line-height',
                                    ' > .super-accordion-item > .super-accordion-header:hover > .super-accordion-title' => 'line-height'
                                )
                            ),
                            'title_font_weight_hover' => array(
                                'name' => esc_html__( 'Title weight (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['title_font_weight_hover'] ) ? 0 : $attributes['title_font_weight_hover'] ),
                                'min' => 0,
                                'max' => 900,
                                'steps' => 100,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab:hover > .super-tab-title' => 'font-weight',
                                    ' > .super-accordion-item > .super-accordion-header:hover > .super-accordion-title' => 'font-weight'
                                )
                            ),
                            // Description size
                            // Description line-height
                            // Description weight
                            'desc_font_size_hover' => array(
                                'name' => esc_html__( 'Description size in pixels (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['desc_font_size_hover'] ) ? 0 : $attributes['desc_font_size_hover'] ),
                                'min' => 0,
                                'max' => 50,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab:hover > .super-tab-desc' => 'font-size',
                                    ' > .super-accordion-item > .super-accordion-header:hover > .super-accordion-desc' => 'font-size'
                                )
                            ),
                            'desc_font_lineheight_hover' => array(
                                'name' => esc_html__( 'Description line-height in pixels (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['desc_font_lineheight_hover'] ) ? 0 : $attributes['desc_font_lineheight_hover'] ),
                                'min' => 0,
                                'max' => 50,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab:hover > .super-tab-desc' => 'line-height',
                                    ' > .super-accordion-item > .super-accordion-header:hover > .super-accordion-desc' => 'line-height'
                                )
                            ),
                            'desc_font_weight_hover' => array(
                                'name' => esc_html__( 'Description weight (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['desc_font_weight_hover'] ) ? 0 : $attributes['desc_font_weight_hover'] ),
                                'min' => 0,
                                'max' => 900,
                                'steps' => 100,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab:hover > .super-tab-desc' => 'font-weight',
                                    ' > .super-accordion-item > .super-accordion-header:hover > .super-accordion-desc' => 'font-weight'
                                )
                            ),
                        )
                    ),
                    'active' => array(
                        'name' => esc_html__( 'Active' , 'super-forms' ),
                        'fields' => array(
                            // Title size
                            // Title line-height
                            // Title weight
                            'title_font_size_active' => array(
                                'name' => esc_html__( 'Title size in pixels (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['title_font_size_active'] ) ? 0 : $attributes['title_font_size_active'] ),
                                'min' => 0,
                                'max' => 50,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab.super-active > .super-tab-title' => 'font-size',
                                    ' > .super-accordion-item.super-active > .super-accordion-header > .super-accordion-title' => 'font-size'
                                )
                            ),
                            'title_font_lineheight_active' => array(
                                'name' => esc_html__( 'Title line-height in pixels (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['title_font_lineheight_active'] ) ? 0 : $attributes['title_font_lineheight_active'] ),
                                'min' => 0,
                                'max' => 50,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab.super-active > .super-tab-title' => 'line-height',
                                    ' > .super-accordion-item.super-active > .super-accordion-header > .super-accordion-title' => 'line-height'
                                )
                            ),
                            'title_font_weight_active' => array(
                                'name' => esc_html__( 'Title weight (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['title_font_weight_active'] ) ? 0 : $attributes['title_font_weight_active'] ),
                                'min' => 0,
                                'max' => 900,
                                'steps' => 100,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab.super-active > .super-tab-title' => 'font-weight',
                                    ' > .super-accordion-item.super-active > .super-accordion-header > .super-accordion-title' => 'font-weight'
                                )
                            ),
                            // Description size
                            // Description line-height
                            // Description weight
                            'desc_font_size_active' => array(
                                'name' => esc_html__( 'Description size in pixels (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['desc_font_size_active'] ) ? 0 : $attributes['desc_font_size_active'] ),
                                'min' => 0,
                                'max' => 50,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab.super-active > .super-tab-desc' => 'font-size',
                                    ' > .super-accordion-item.super-active > .super-accordion-header > .super-accordion-desc' => 'font-size'
                                )
                            ),
                            'desc_font_lineheight_active' => array(
                                'name' => esc_html__( 'Description line-height in pixels (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['desc_font_lineheight_active'] ) ? 0 : $attributes['desc_font_lineheight_active'] ),
                                'min' => 0,
                                'max' => 50,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab.super-active > .super-tab-desc' => 'line-height',
                                    ' > .super-accordion-item.super-active > .super-accordion-header > .super-accordion-desc' => 'line-height'
                                )
                            ),
                            'desc_font_weight_active' => array(
                                'name' => esc_html__( 'Description weight (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['desc_font_weight_active'] ) ? 0 : $attributes['desc_font_weight_active'] ),
                                'min' => 0,
                                'max' => 900,
                                'steps' => 100,
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab.super-active > .super-tab-desc' => 'font-weight',
                                    ' > .super-accordion-item.super-active > .super-accordion-header > .super-accordion-desc' => 'font-weight'
                                )
                            )
                        )
                    )
                ),
                // Padding & margins styles
                'padding_styles' => array(
                    'name' => esc_html__( 'Padding & margin styles', 'super-forms' ),
                    'default' => array(
                        'name' => esc_html__( 'Default' , 'super-forms' ),
                        'fields' => array(
                            'content_padding' => array(
                                'name' => esc_html__( 'Content padding', 'super-forms' ),
                                'default' => (!isset($attributes['content_padding']) ? '' : $attributes['content_padding']),
                                '_styles' => array(
                                    ' > .super-tabs-contents > .super-tabs-content.super-active' => 'padding',
                                    ' > .super-accordion-item.super-active > .super-accordion-content' => 'padding'
                                ),
                            ),
                            'tab_padding' => array(
                                'name' => esc_html__( 'Tab padding', 'super-forms' ),
                                'default' => (!isset($attributes['tab_padding']) ? '' : $attributes['tab_padding']),
                                '_styles' => array(
                                    ' > .super-tabs-menu > .super-tabs-tab' => 'padding',
                                    ' > .super-accordion-item > .super-accordion-header' => 'padding'
                                ),
                            ),
                            'bottom_margin' => array(
                                'name' => esc_html__( 'Margin between items (bottom margin / spacing)', 'super-forms' ),
                                'default' => (!isset($attributes['bottom_margin']) ? 0 : $attributes['bottom_margin']),
                                'type' => 'slider', 
                                'min' => 0,
                                'max' => 100,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item' => 'margin-bottom'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                        )
                    ),
                    'hover' => array(
                        'name' => esc_html__( 'Hover' , 'super-forms' ),
                        'fields' => array(
                            'tab_padding_hover' => array(
                                'name' => esc_html__( 'Tab padding', 'super-forms' ),
                                'default' => (!isset($attributes['tab_padding_hover']) ? '' : $attributes['tab_padding_hover']),
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header:hover' => 'padding'
                                ),
                            ),
                        )
                    ),
                    'active' => array(
                        'name' => esc_html__( 'Active' , 'super-forms' ),
                        'fields' => array(
                            'tab_padding_active' => array(
                                'name' => esc_html__( 'Tab padding', 'super-forms' ),
                                'default' => (!isset($attributes['tab_padding_active']) ? '' : $attributes['tab_padding_active']),
                                '_styles' => array(
                                    ' > .super-accordion-item.super-active > .super-accordion-header' => 'padding'
                                ),
                            ),
                        )
                    )
                ),
                // Border styles
                'border_styles' => array(
                    'name' => esc_html__( 'Border styles', 'super-forms' ),
                    'default' => array(
                        'name' => esc_html__( 'Default' , 'super-forms' ),
                        'fields' => array(
                            'tab_border_top_width' => array(
                                'name' => esc_html__( 'Tab border top width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['tab_border_top_width'] ) ? 0 : $attributes['tab_border_top_width'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header' => 'border-top-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            'tab_border_right_width' => array(
                                'name' => esc_html__( 'Tab border right width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['tab_border_right_width'] ) ? 0 : $attributes['tab_border_right_width'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header' => 'border-right-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            'tab_border_bottom_width' => array(
                                'name' => esc_html__( 'Tab border bottom width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['tab_border_bottom_width'] ) ? 0 : $attributes['tab_border_bottom_width'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header' => 'border-bottom-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            'tab_border_left_width' => array(
                                'name' => esc_html__( 'Tab border left width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['tab_border_left_width'] ) ? 0 : $attributes['tab_border_left_width'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header' => 'border-left-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            'tab_border_color' => array(
                                'name' => esc_html__( 'Tab border color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['tab_border_color']) ? '' : $attributes['tab_border_color']),
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header' => 'border-color'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            'accordion_content_border_top_width' => array(
                                'name' => esc_html__( 'Content border top width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['accordion_content_border_top_width'] ) ? 0 : $attributes['accordion_content_border_top_width'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item.super-active > .super-accordion-content' => 'border-top-width',
                                    '.super-horizontal > .super-tabs-contents' => 'border-top-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'tabs,accordion'
                            ),
                            'accordion_content_border_right_width' => array(
                                'name' => esc_html__( 'Content border right width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['accordion_content_border_right_width'] ) ? 0 : $attributes['accordion_content_border_right_width'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item.super-active > .super-accordion-content' => 'border-right-width',
                                    '.super-horizontal > .super-tabs-contents' => 'border-right-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'tabs,accordion'
                            ),
                            'accordion_content_border_bottom_width' => array(
                                'name' => esc_html__( 'Content border bottom width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['accordion_content_border_bottom_width'] ) ? 0 : $attributes['accordion_content_border_bottom_width'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item.super-active > .super-accordion-content' => 'border-bottom-width',
                                    '.super-horizontal > .super-tabs-contents' => 'border-bottom-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'tabs,accordion'
                            ),
                            'accordion_content_border_left_width' => array(
                                'name' => esc_html__( 'Content border left width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['accordion_content_border_left_width'] ) ? 0 : $attributes['accordion_content_border_left_width'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item.super-active > .super-accordion-content' => 'border-left-width',
                                    '.super-horizontal > .super-tabs-contents' => 'border-left-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'tabs,accordion'
                            ),
                            'accordion_content_border_color' => array(
                                'name' => esc_html__( 'Content border color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['accordion_content_border_color']) ? '' : $attributes['accordion_content_border_color']),
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-content' => 'border-color'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            'content_border_color' => array(
                                'name' => esc_html__( 'Content border color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['content_border_color']) ? '' : $attributes['content_border_color']),
                                '_styles' => array(
                                    //'.super-horizontal > .super-tabs-menu > .super-tabs-tab.super-active' => 'border-left-color,border-right-color',
                                    //'.super-horizontal > .super-tabs-contents > .super-tabs-content' => 'border-top-color',
                                    //'.super-vertical > .super-tabs-contents > .super-tabs-content' => 'border-left-color',
                                    ' > .super-accordion-item > .super-accordion-content' => 'border-color',
                                    '.super-horizontal > .super-tabs-contents' => 'border-color'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'tabs'
                            ),
                        )
                    ),
                    'hover' => array(
                        'name' => esc_html__( 'Hover' , 'super-forms' ),
                        'fields' => array(
                            'tab_border_top_width_hover' => array(
                                'name' => esc_html__( 'Tab border top width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['tab_border_top_width_hover'] ) ? 0 : $attributes['tab_border_top_width_hover'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header' => 'border-top-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            'tab_border_right_width_hover' => array(
                                'name' => esc_html__( 'Tab border right width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['tab_border_right_width_hover'] ) ? 0 : $attributes['tab_border_right_width_hover'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header' => 'border-right-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            'tab_border_bottom_width_hover' => array(
                                'name' => esc_html__( 'Tab border bottom width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['tab_border_bottom_width_hover'] ) ? 0 : $attributes['tab_border_bottom_width_hover'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header' => 'border-bottom-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            'tab_border_left_width_hover' => array(
                                'name' => esc_html__( 'Tab border left width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['tab_border_left_width_hover'] ) ? 0 : $attributes['tab_border_left_width_hover'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header' => 'border-left-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            'tab_border_color_hover' => array(
                                'name' => esc_html__( 'Tab border color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['tab_border_color_hover']) ? '' : $attributes['tab_border_color_hover']),
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header' => 'border-color'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                        )
                    ),
                    'active' => array(
                        'name' => esc_html__( 'Active' , 'super-forms' ),
                        'fields' => array(
                            'tab_border_top_width_active' => array(
                                'name' => esc_html__( 'Tab border top width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['tab_border_top_width_active'] ) ? 0 : $attributes['tab_border_top_width_active'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header' => 'border-top-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            'tab_border_right_width_active' => array(
                                'name' => esc_html__( 'Tab border right width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['tab_border_right_width_active'] ) ? 0 : $attributes['tab_border_right_width_active'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header' => 'border-right-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            'tab_border_bottom_width_active' => array(
                                'name' => esc_html__( 'Tab border bottom width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['tab_border_bottom_width_active'] ) ? 0 : $attributes['tab_border_bottom_width_active'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header' => 'border-bottom-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            'tab_border_left_width_active' => array(
                                'name' => esc_html__( 'Tab border left width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['tab_border_left_width_active'] ) ? 0 : $attributes['tab_border_left_width_active'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header' => 'border-left-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            'tab_border_color_active' => array(
                                'name' => esc_html__( 'Tab border color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['tab_border_color_active']) ? '' : $attributes['tab_border_color_active']),
                                '_styles' => array(
                                    ' > .super-accordion-item > .super-accordion-header' => 'border-color'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'accordion'
                            ),
                            'tab_vertical_border_top_width_active' => array(
                                'name' => esc_html__( 'Tab border top width (0 = none)', 'super-forms' ), 
                                'type' => 'slider', 
                                'default' => ( !isset( $attributes['tab_vertical_border_top_width_active'] ) ? 0 : $attributes['tab_vertical_border_top_width_active'] ),
                                'min' => 0,
                                'max' => 10,
                                'steps' => 1,
                                '_styles' => array(
                                    '.super-horizontal > .super-tabs-menu > .super-tabs-tab' => 'border-top-width'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'tabs'
                            ),
                            'tab_vertical_border_top_color_active' => array(
                                'name' => esc_html__( 'Tab border color', 'super-forms' ),
                                'type' => 'color',
                                'default' => (!isset($attributes['tab_vertical_border_top_color_active']) ? '' : $attributes['tab_vertical_border_top_color_active']),
                                '_styles' => array(
                                    '.super-horizontal > .super-tabs-menu > .super-tabs-tab.super-active' => 'border-top-color'
                                ),
                                'filter' =>true,
                                'parent' => 'layout',
                                'filter_value' => 'tabs'
                            ),
                        )
                    )
                )
            )
        )
    )
);
