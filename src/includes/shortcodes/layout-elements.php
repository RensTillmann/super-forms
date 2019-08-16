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
                            'default' => (!isset($attributes['invisible']) ? '' : $attributes['invisible']),
                            'type' => 'select',
                            'values' => array(
                                '' => 'No',
                                'true' => 'Yes',
                            )
                        ),
                        'duplicate' => array(
                            'name' =>esc_html__( 'Enable Add More', 'super-forms' ),
                            'desc' =>esc_html__( 'Let users duplicate the fields inside this column', 'super-forms' ),
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
                            'desc' => esc_html__( 'The total of times a user can click the "+" icon', 'super-forms' ), 
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
                            'desc' => esc_html__( 'When enabled this will update conditional logic, {tags} and variable fields dynamically', 'super-forms' ), 
                            'default' => ( !isset( $attributes['duplicate_dynamically'] ) ? 'true' : $attributes['duplicate_dynamically'] ),
                            'type' => 'checkbox', 
                            'values' => array(
                                'true' => esc_html__( 'Update conditional logic, {tags} and variable fields dynamically', 'super-forms' ),
                            ),
                            'filter' =>true,
                            'parent' => 'duplicate',
                            'filter_value' => 'enabled'                            
                        ),

                        'label' => array(
                            'name' => esc_html__( 'Column Label', 'super-forms' ),
                            'desc' => esc_html__( 'This makes it easier to keep track of your sections when building forms', 'super-forms' ),
                            'default' => ( !isset( $attributes['label'] ) ? 'Column' : $attributes['label'] )
                        ),

                        // @since 1.9
                        'class' => array(
                            'name' => esc_html__( 'Custom class', 'super-forms' ),
                            'desc' => '(' . esc_html__( 'Add a custom class to append extra styles', 'super-forms' ) . ')',
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
                    'data' => array(
                        'step_name' => esc_html__( 'Step 1', 'super-forms' ),
                        'step_description' => esc_html__( 'Description for this step', 'super-forms' ),
                        'icon' => 'user',
                    )
                )            
            ),
            'atts' => array(),
            'html' => '<span>' . esc_html__( 'Multi Part', 'super-forms' ) . '</span>',
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

                        'step_name' => array(
                            'name' =>esc_html__( 'Step Name', 'super-forms' ),
                            'default' => (!isset($attributes['step_name']) ? esc_html__( 'Step 1', 'super-forms' )  : $attributes['step_name']),
                            'type' => 'text',
                            'i18n' => true 
                        ),
                        'step_description' => array(
                            'name' =>esc_html__( 'Step Description', 'super-forms' ),
                            'default' => (!isset($attributes['step_description']) ? esc_html__( 'Description for this step', 'super-forms' ) : $attributes['step_description']),
                            'type' => 'text',
                            'i18n' => true
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

                        'icon' => array(
                            'default' => (!isset($attributes['icon']) ? 'user' : $attributes['icon']),
                            'name' =>esc_html__( 'Select an Icon', 'super-forms' ), 
                            'type' => 'icon',
                            'desc' =>esc_html__( 'Leave blank if you prefer to not use an icon.', 'super-forms' ),
                        )
                    )
                )
            )
        )
        // 'tabs_pre' => array(
        //     'name' => esc_html__( 'Tabs', 'super-forms' ),
        //     'predefined' => array(
        //         array(
        //             'tag' => 'tabs',
        //             'group' => 'layout_elements',
        //             'inner' => '',
        //             'data' => array(
        //                 'layout' => 'tabs'
        //             )
        //         )
        //     ),
        //     'atts' => array(),
        //     'html' => '<span>' . esc_html__( 'Tabs', 'super-forms' ) . '</span>'
        // ),
        // 'accordion_pre' => array(
        //     'name' => esc_html__( 'Accordion', 'super-forms' ),
        //     'predefined' => array(
        //         array(
        //             'tag' => 'tabs',
        //             'group' => 'layout_elements',
        //             'inner' => '',
        //             'data' => array(
        //                 'layout' => 'accordion'
        //             )
        //         )
        //     ),
        //     'atts' => array(),
        //     'html' => '<span>' . esc_html__( 'Accordion', 'super-forms' ) . '</span>'
        // ),
        // 'tabs' => array(
        //     'callback' => 'SUPER_Shortcodes::tabs',
        //     'hidden' => true,
        //     'drop' => true,
        //     'name' => esc_html__( 'Tabs/Accordion', 'super-forms' ),
        //     'atts' => array(
        //         'general' => array(
        //             'name' => esc_html__( 'General', 'super-forms' ),
        //             'fields' => array(
        //                 'items' => array(
        //                     'type' => 'tab_items',
        //                     'default' => ( !isset( $attributes['dropdown_items'] ) ? 
        //                         array(
        //                             array(
        //                                 'image' => '',
        //                                 'title' => esc_html__( 'Tab', 'super-forms' ) . ' 1',
        //                                 'desc' => esc_html__( 'Description', 'super-forms' )
        //                             ),
        //                             array(
        //                                 'image' => '',
        //                                 'title' => esc_html__( 'Tab', 'super-forms' ) . ' 2',
        //                                 'desc' => esc_html__( 'Description', 'super-forms' )
        //                             ),
        //                             array(
        //                                 'image' => '',
        //                                 'title' => esc_html__( 'Tab', 'super-forms' ) . ' 3',
        //                                 'desc' => esc_html__( 'Description', 'super-forms' )
        //                             )
        //                         ) : $value
        //                     ),
        //                     'i18n' => true
        //                 ),
        //                 'layout' => array(
        //                     'name' => esc_html__( 'Layout', 'super-forms' ),
        //                     'default' => ( !isset( $attributes['layout'] ) ? 'tabs' : $attributes['layout'] ),
        //                     'type' => 'image_select',
        //                     'values' => array(
        //                         'tabs' => array(
        //                             'title' => esc_html__( 'Tabs (default)', 'super-forms' ),
        //                             'icon' => 'far fa-folder'
        //                         ),
        //                         'accordion' => array(
        //                             'title' => esc_html__( 'Accordion', 'super-forms' ),
        //                             'icon' => 'far fa-caret-square-down'
        //                         ),
        //                         'list' => array(
        //                             'title' => esc_html__( 'List', 'super-forms' ),
        //                             'icon' => 'fas fa-list'
        //                         )
        //                     ),
        //                     'filter' => true
        //                 ),
        //                 'tab_location' => array(
        //                     'name' => esc_html__( 'Tab Location', 'super-forms' ),
        //                     'default' => ( !isset( $attributes['tab_location'] ) ? 'horizontal' : $attributes['tab_location'] ),
        //                     'type' => 'select',
        //                     'values' => array(
        //                         'horizontal' => esc_html__( 'Horizontal tabs', 'super-forms' ),
        //                         'vertical' => esc_html__( 'Vertical tabs', 'super-forms' ),
        //                     ),
        //                     'filter' => true,
        //                     'parent' => 'layout',
        //                     'filter_value' => 'tabs'
        //                 ),
        //                 'tab_class' => array(
        //                     'name' => esc_html__( 'Custom TAB class', 'super-forms' ),
        //                     'desc' => '(' . esc_html__( 'Add a custom TAB class to append extra styles', 'super-forms' ) . ')',
        //                     'default' => ( !isset( $attributes['tab_class'] ) ? '' : $attributes['tab_class'] ),
        //                     'type' => 'text',
        //                 ),
        //                 'content_class' => array(
        //                     'name' => esc_html__( 'Custom Content class', 'super-forms' ),
        //                     'desc' => '(' . esc_html__( 'Add a custom Content class to append extra styles', 'super-forms' ) . ')',
        //                     'default' => ( !isset( $attributes['content_class'] ) ? '' : $attributes['content_class'] ),
        //                     'type' => 'text',
        //                 )
        //             )
        //         ),
        //     )
        // )
    )
);
