<?php
$array['layout_elements'] = array(
    'title' => __( 'Layout Elements', 'super-forms' ),   
    'class' => 'super-layout-elements',
    'info' => __( 'Use it as a starting point, but you can customize the columns', 'super-forms' ),
    'shortcodes' => array(
        'column_one_full' => array(
            'name' => __( 'Column', 'super-forms' ),
            'icon' => 'user',
            'predefined' => array(
                array(
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array(
                        'size' => '1/1',
                        'margin' => '',
                        'conditional_action' => 'disabled',
                    )
                )            
            ),
            'atts' => array(),
            'html' => '<span>1/1</span>',
        ),
        'column_one_half' => array(
            'name' => __( 'Column', 'super-forms' ),
            'icon' => 'user',
            'predefined' => array(
                array(
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array(
                        'size' => '1/2',
                        'margin' => '',
                        'conditional_action' => 'disabled',
                    )
                ),
                array(
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array(
                        'size' => '1/2',
                        'margin' => '',
                        'conditional_action' => 'disabled',
                    )
                )           
            ),
            'atts' => array(),
            'html' => '<span>1/2</span><span>1/2</span>',
        ),
        'column_one_third' => array(
            'name' => __( 'Column', 'super-forms' ),
            'icon' => 'user',
            'predefined' => array(
                array(
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array(
                        'size' => '1/3',
                        'margin' => '',
                        'conditional_action' => 'disabled',
                    )
                ),
                array(
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array(
                        'size' => '1/3',
                        'margin' => '',
                        'conditional_action' => 'disabled',
                    )
                ),
                array(
                    'tag' => 'column',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array(
                        'size' => '1/3',
                        'margin' => '',
                        'conditional_action' => 'disabled',
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
                    'name' => __( 'General', 'super-forms' ),
                    'fields' => array(
                        'size' => array(
                            'name'=>'Column size',
                            'default'=> (!isset($attributes['size']) ? '1/1' : $attributes['size']),
                            'type'=>'select',
                            'values'=>array(
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
                            'name'=>__( 'Make column invisible', 'super-forms' ),
                            'default'=> (!isset($attributes['invisible']) ? '' : $attributes['invisible']),
                            'type'=>'select',
                            'values'=>array(
                                ''=>'No',
                                'true'=>'Yes',
                            )
                        ),
                        'duplicate' => array(
                            'name'=>__( 'Enable Add More', 'super-forms' ),
                            'desc'=>__( 'Let users duplicate the fields inside this column', 'super-forms' ),
                            'default'=> ( !isset( $attributes['duplicate'] ) ? '' : $attributes['duplicate'] ),
                            'type'=>'select',
                            'values'=>array(
                                ''=>'Disabled',
                                'enabled'=>'Enabled (allows users to add dynamic fields)',
                            ),
                            'filter'=>true,
                        ),
                        'duplicate_limit' => array(
                            'name' => __( 'Limit for dynamic fields (0 = unlimited)', 'super-forms' ), 
                            'desc' => __( 'The total of times a user can click the "+" icon', 'super-forms' ), 
                            'type' => 'slider', 
                            'default'=> ( !isset( $attributes['duplicate_limit'] ) ? 0 : $attributes['duplicate_limit'] ),
                            'min' => 0,
                            'max' => 50,
                            'steps' => 1,
                            'filter'=>true,
                            'parent'=>'duplicate',
                            'filter_value'=>'enabled'
                        ),

                        // @since 1.3
                        'duplicate_dynamically' => array(
                            'desc' => __( 'When enabled this will update conditional logic dynamically', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['duplicate_dynamically'] ) ? '' : $attributes['duplicate_dynamically'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Update conditional logic dynamically', 'super-forms' ),
                            )
                        ),

                        // @since 1.9
                        'class' => array(
                            'name' => __( 'Custom class', 'super-forms' ),
                            'desc' => '(' . __( 'Add a custom class to append extra styles', 'super-forms' ) . ')',
                            'default'=> ( !isset( $attributes['class'] ) ? '' : $attributes['class'] ),
                            'type'=>'text',
                        )

                    )
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super-forms' ),
                    'fields' => array(

                        // @since 1.9
                        'bg_image' => array(
                            'name'=>__( 'Background image', 'super-forms' ),
                            'default'=> ( !isset( $attributes['bg_image']) ? '' : $attributes['bg_image']),
                            'type'=>'image',
                        ),

                        // @since 1.3
                        'bg_color' => array(
                            'name'=>__( 'Background color', 'super-forms' ),
                            'default'=> (!isset($attributes['bg_color']) ? '' : $attributes['bg_color']),
                            'type'=>'color',
                        ),

                        // @since 1.9
                        'bg_opacity' => array(
                            'name'=>__( 'Background color opacity', 'super-forms' ),
                            'type' => 'slider', 
                            'default'=> ( !isset( $attributes['bg_opacity'] ) ? 1 : $attributes['bg_opacity'] ),
                            'min' => 0,
                            'max' => 1,
                            'steps' => 0.1,
                        ),

                        // @since 1.3
                        'enable_padding' => array(
                            'desc' => __( 'Use custom padding', 'super-forms' ), 
                            'default'=> ( !isset( $attributes['enable_padding'] ) ? '' : $attributes['enable_padding'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Enable custom padding', 'super-forms' ),
                            )
                        ),
                        // @since 1.3
                        'padding' => array(
                            'name' => __( 'Column paddings example: 0px 0px 0px 0px', 'super-forms' ),
                            'label' => __( '(leave blank for no custom paddings)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['padding'] ) ? '' : $attributes['padding'] ),
                            'type'=>'text',
                            'filter'=>true,
                            'parent'=>'enable_padding',
                            'filter_value'=>'true'
                        ),

                        'margin' => array(
                            'name'=>__( 'Remove margin', 'super-forms' ),
                            'default'=> (!isset($attributes['margin']) ? '' : $attributes['margin']),
                            'type'=>'select',
                            'values'=>array(
                                ''=>'No',
                                'no_margin'=>'Yes',
                            )
                        ),

                        // @since 1.9
                        'position' => array(
                            'name'=>__( 'Positioning method', 'super-forms' ),
                            'default'=> (!isset($attributes['position']) ? '' : $attributes['position']),
                            'type'=>'select',
                            'values'=>array(
                                ''=> __( 'Static (default)', 'super-forms' ),
                                'relative'=> __( 'Relative', 'super-forms' ),
                                'absolute'=> __( 'Absolute', 'super-forms' ),
                                'fixed'=> __( 'Fixed (not recommended)', 'super-forms' ),
                            ),
                            'filter'=>true,
                        ),
                        'positioning' => array(
                            'name'=>__( 'Positioning method', 'super-forms' ),
                            'default'=> (!isset($attributes['positioning']) ? '' : $attributes['positioning']),
                            'type'=>'select',
                            'values'=>array(
                                ''=> __( 'None', 'super-forms' ),
                                'top_left'=> __( 'Top and Left', 'super-forms' ),
                                'top_right'=> __( 'Top and Right', 'super-forms' ),
                                'bottom_left'=> __( 'Bottom and Left', 'super-forms' ),
                                'bottom_right'=> __( 'Bottom and Right', 'super-forms' ),
                            ),
                            'filter'=>true,
                            'parent'=>'position',
                            'filter_value'=>'relative,absolute,fixed'
                        ),
                        'positioning_top' => array(
                            'name' => __( 'Positioning top e.g: 10px', 'super-forms' ),
                            'default'=> ( !isset( $attributes['positioning_top'] ) ? '' : $attributes['positioning_top'] ),
                            'type'=>'text',
                            'filter'=>true,
                            'parent'=>'positioning',
                            'filter_value'=>'top_left,top_right'
                        ),
                        'positioning_right' => array(
                            'name' => __( 'Positioning right e.g: 10px', 'super-forms' ),
                            'default'=> ( !isset( $attributes['positioning_right'] ) ? '' : $attributes['positioning_right'] ),
                            'type'=>'text',
                            'filter'=>true,
                            'parent'=>'positioning',
                            'filter_value'=>'top_right,bottom_right'
                        ),
                        'positioning_bottom' => array(
                            'name' => __( 'Positioning bottom e.g: 10px', 'super-forms' ),
                            'default'=> ( !isset( $attributes['positioning_bottom'] ) ? '' : $attributes['positioning_bottom'] ),
                            'type'=>'text',
                            'filter'=>true,
                            'parent'=>'positioning',
                            'filter_value'=>'bottom_left,bottom_right'
                        ),
                        'positioning_left' => array(
                            'name' => __( 'Positioning left e.g: 10px', 'super-forms' ),
                            'default'=> ( !isset( $attributes['positioning_left'] ) ? '' : $attributes['positioning_left'] ),
                            'type'=>'text',
                            'filter'=>true,
                            'parent'=>'positioning',
                            'filter_value'=>'top_left,bottom_left'
                        ),
                    )
                ),

                // @since 1.9
                'responsiveness' => array(
                    'name' => __( 'Responsiveness', 'super-forms' ),
                    'fields' => array(
                        'hide_on_mobile' => array(
                            'name' => __( 'Based on form width (breaking point = 760px)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['hide_on_mobile'] ) ? '' : $attributes['hide_on_mobile'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Hide on mobile devices', 'super-forms' ),
                            )
                        ),
                        'resize_disabled_mobile' => array(
                            'default'=> ( !isset( $attributes['resize_disabled_mobile'] ) ? '' : $attributes['resize_disabled_mobile'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Keep original size on mobile devices (prevents 100% width)', 'super-forms' ),
                            )
                        ),
                        'hide_on_mobile_window' => array(
                            'name' => __( 'Based on screen width (breaking point = 760px)', 'super-forms' ),
                            'default'=> ( !isset( $attributes['hide_on_mobile_window'] ) ? '' : $attributes['hide_on_mobile_window'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Hide on mobile devices', 'super-forms' ),
                            )
                        ),
                        'resize_disabled_mobile_window' => array(
                            'default'=> ( !isset( $attributes['resize_disabled_mobile_window'] ) ? '' : $attributes['resize_disabled_mobile_window'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Keep original size on mobile devices (prevents 100% width)', 'super-forms' ),
                            )
                        ),
                        'force_responsiveness_mobile_window' => array(
                            'default'=> ( !isset( $attributes['force_responsiveness_mobile_window'] ) ? '' : $attributes['force_responsiveness_mobile_window'] ),
                            'type' => 'checkbox', 
                            'filter'=>true,
                            'values' => array(
                                'true' => __( 'Force responsiveness on mobile devices (always 100% width)', 'super-forms' ),
                            )
                        ),

                    )
                ),

                'conditional_logic' => $conditional_logic_array
            )
        ),
        'multipart_pre' => array(
            'name' => __( 'Multi Part', 'super-forms' ),
            'icon' => 'user',
            'predefined' => array(
                array(
                    'tag' => 'multipart',
                    'group' => 'layout_elements',
                    'inner' => '',
                    'data' => array(
                        'step_name' => __( 'Step 1', 'super-forms' ),
                        'step_description' => __( 'Description for this step', 'super-forms' ),
                        'icon' => 'user',
                    )
                )            
            ),
            'atts' => array(),
            'html' => '<span>Multi Part</span>',
        ),
        'multipart' => array(
            'callback' => 'SUPER_Shortcodes::multipart',
            'hidden' => true,
            'drop' => true,
            'content' => ((!isset($content) || ($content=='')) ? '' : $content),
            'content_hidden' => true,
            'name' => __( 'Multi Part', 'super-forms' ),
            'atts' => array(
                'multi_part' => array(
                    'name' => __( 'Multi Part', 'super-forms' ),
                    'fields' => array(
                        'auto' => array(
                            'name'=>__( 'Automatically go to next step', 'super-forms' ),
                            'desc'=>__( 'After last field is filled out, go to next step automatically', 'super-forms' ),
                            'default'=> ( !isset( $attributes['auto'] ) ? 'no' : $attributes['auto'] ),
                            'type'=>'select',
                            'values'=>array(
                                'no'=>__( 'No (disabled)', 'super-forms' ),
                                'yes'=>__( 'Yes (enabled)', 'super-forms' )
                            )
                        ),
                        'validate' => array(
                            'desc'=>__( 'Prevent users from going to next step if it contains errors', 'super-forms' ),
                            'default'=> ( !isset( $attributes['validate'] ) ? '' : $attributes['validate'] ),
                            'type'=>'checkbox',
                            'values'=>array(
                                'true'=>__( 'Check for errors before going to next step', 'super-forms' ),
                            )
                        ),
                        'step_name' => array(
                            'name'=>__( 'Step Name', 'super-forms' ),
                            'default'=> (!isset($attributes['step_name']) ? __( 'Step 1', 'super-forms' )  : $attributes['step_name']),
                            'type'=>'text', 
                        ),
                        'step_description' => array(
                            'name'=>__( 'Step Description', 'super-forms' ),
                            'default'=> (!isset($attributes['step_description']) ? __( 'Description for this step', 'super-forms' ) : $attributes['step_description']),
                            'type'=>'text',
                        ),
                        'prev_text' => array(
                            'name'=>__( 'Previous button text', 'super-forms' ),
                            'default'=> (!isset($attributes['prev_text']) ? __( 'Prev', 'super-forms' )  : $attributes['prev_text']),
                            'type'=>'text', 
                        ),
                        'next_text' => array(
                            'name'=>__( 'Next button text', 'super-forms' ),
                            'default'=> (!isset($attributes['next_text']) ? __( 'Next', 'super-forms' )  : $attributes['next_text']),
                            'type'=>'text', 
                        ),
                        
                        // @since 1.9
                        'class' => array(
                            'name' => __( 'Custom class', 'super-forms' ),
                            'desc' => '(' . __( 'Add a custom class to append extra styles', 'super-forms' ) . ')',
                            'default'=> ( !isset( $attributes['class'] ) ? '' : $attributes['class'] ),
                            'type'=>'text',
                        ),

                        'icon' => array(
                            'default'=> (!isset($attributes['icon']) ? 'user' : $attributes['icon']),
                            'name'=>__( 'Select an Icon', 'super-forms' ), 
                            'type'=>'icon',
                            'desc'=>__( 'Leave blank if you prefer to not use an icon.', 'super-forms' ),
                        )
                    )
                )
            )
        )
    )
);