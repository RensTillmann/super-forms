<?php
$array['layout_elements'] = array(
    'title' => __( 'Layout', 'super-forms' ),   
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
                        'margin' => array(
                            'name'=>__( 'Remove margin', 'super-forms' ),
                            'default'=> (!isset($attributes['margin']) ? '' : $attributes['margin']),
                            'type'=>'select',
                            'values'=>array(
                                ''=>'No',
                                'no_margin'=>'Yes',
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
                            )
                        )
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