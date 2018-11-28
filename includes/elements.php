<?php

function sf_column_settings($default){
    return array(
        'size' => array(
            'type' => 'buttons',
            'title' => __( 'Column size', 'super-forms' ),
            'default' => '1/1',
            'options' => array(
                '1/1' => '1/1',
                '4/5' => '4/5',
                '3/4' => '3/4',
                '2/3' => '2/3',
                '3/5' => '3/5',
                '1/2' => '1/2',
                '2/5' => '2/5',
                '1/3' => '1/3',
                '1/4' => '1/4',
                '1/5' => '1/5'
            ),
            'selector' => '.super-column-wrapper',
            'style_update' => 'column_size'
        )
    );
}

return array(
    'column' => array(
        'title' => __( 'Column 1/1', 'super-forms' ),
        'preview' => '<div class="sf-column sf-1-1"></div>',
        'class' => 'sf-1-1',
        'inner' => array(
            'text' => 1, // The element name and the times to add it
        ),
        'settings' => sf_column_settings('1/1')
    ),
    'column2' => array(
        'title' => __( 'Column 1/2', 'super-forms' ),
        'preview' => '<div class="sf-column sf-1-2"></div><div class="sf-column sf-1-2"></div>',
        'class' => 'sf-1-2',
        'inner' => array(
            'text' => 1, // The element name and the times to add it
        ),
        'times' => 2, // The amount of times this element should be added to wherever the user wants to drop it
        'settings' => sf_column_settings('1/2')
    ),
    'column3' => array(
        'title' => __( 'Column 1/3', 'super-forms' ),
        'preview' => '<div class="sf-column sf-1-3"></div><div class="sf-column sf-1-3"></div><div class="sf-column sf-1-3"></div>',
        'class' => 'sf-1-3',
        'inner' => array(
            'text' => 1, // The element name and the times to add it
        ),
        'times' => 3, // The amount of times this element should be added to wherever the user wants to drop it
        'settings' => sf_column_settings('1/3')
    ),
    'text' => array(
        'title' => __( 'Text field', 'super-forms' ),
        'preview' => '<input type="text" placeholder="'.__( 'Dummy placeholder...', 'super-forms' ).'" />',
        'html' => '<div class="sf-label-desc"><div class="sf-label">'.__( 'Dummy label', 'super-forms' ).'</div></div><div class="sf-field"><input type="text" placeholder="'.__( 'Dummy placeholder...', 'super-forms' ).'" /></div>',
        'settings' => array(
            'name' => array(
                'type' => 'text',
                'title' => __( 'Field name', 'super-forms' ),
                'value' => __( 'field', 'super-forms' ),
                'required' => true
            ),
            'placeholder' => array(
                'value' => array(
                    'type' => 'text',
                    'title' => __( 'Placeholder', 'super-forms' ),
                    'default' => __( 'Dummy placeholder...', 'super-forms' )
                )
            ),
            'label' => array(
                'value' => array(
                    'type' => 'text',
                    'title' => __( 'Label', 'super-forms' ),
                    'docs' => 'tags-system',
                    'default' => __( 'Dummy label', 'super-forms' )
                ),
                'font' => array(
                    'color' => array(
                        'type' => 'color_picker',
                        'title' => __( 'Color', 'super-forms' ),
                        'default' => '#000',
                        'selector' => '.super-label',
                        'style_update' => 'color'
                    ),
                    'family' => array(
                        'type' => 'dropdown;font',
                        'title' => __( 'Family', 'super-forms' ),
                        'default' => 'Default',
                        'selector' => '.super-label',
                        'style_update' => 'fontFamily'
                    ),
                    'size' => array(
                        'type' => 'slider',
                        'title' => __( 'Size', 'super-forms' ),
                        'min' => 1,
                        'max' => 50,
                        'step' => 1,
                        'units' => array(
                            'px', 
                            'em', 
                            'rem'
                        ),
                        'default' => array(
                            'value' => '12',
                            'unit' => 'px'
                        ),
                        'selector' => '.super-label',
                        'style_update' => 'fontSize'
                    ),
                    'weight' => array(
                        'type' => 'dropdown',
                        'title' => __( 'Weight', 'super-forms' ),
                        'default' => 'default',
                        'options' => array(
                            '100' => '100',
                            '200' => '200',
                            '300' => '300',
                            '400' => '400',
                            '500' => '500',
                            '600' => '600',
                            '700' => '700',
                            '800' => '800',
                            '900' => '900',
                            'default' => __( 'Default', 'super-forms' ),
                            'normal' => __( 'Normal', 'super-forms' ),
                            'bold' => __( 'Bold', 'super-forms' )
                        ),
                        'selector' => '.super-label',
                        'style_update' => 'fontWeight'
                    ),
                    'transform' => array(
                        'type' => 'dropdown',
                        'title' => __( 'Transform', 'super-forms' ),
                        'default' => 'default',
                        'options' => array(
                            'default' => __( 'Default', 'super-forms' ),
                            'uppercase' => __( 'Uppercase', 'super-forms' ),
                            'lowercase' => __( 'Lowercase', 'super-forms' ),
                            'capitalize' => __( 'Capitalize', 'super-forms' ),
                            'normal' => __( 'Normal', 'super-forms' )
                        ),
                        'selector' => '.super-label',
                        'style_update' => 'textTransform'
                    ),
                    'style' => array(
                        'type' => 'dropdown',
                        'title' => __( 'Style', 'super-forms' ),
                        'default' => 'default',
                        'options' => array(
                            'default' => __( 'Default', 'super-forms' ),
                            'normal' => __( 'Normal', 'super-forms' ),
                            'italic' => __( 'Italic', 'super-forms' ),
                            'oblique' => __( 'Oblique', 'super-forms' )
                        ),
                        'selector' => '.super-label',
                        'style_update' => 'fontStyle'
                    ),
                    'decoration' => array(
                        'type' => 'dropdown',
                        'title' => __( 'Decoration', 'super-forms' ),
                        'default' => 'default',
                        'options' => array(
                            'default' => __( 'Default', 'super-forms' ),
                            'underline' => __( 'Underline', 'super-forms' ),
                            'overline' => __( 'Overline', 'super-forms' ),
                            'line-through' => __( 'Line Through', 'super-forms' ),
                            'none' => __( 'None', 'super-forms' )
                        ),
                        'selector' => '.super-label',
                        'style_update' => 'textDecoration'
                    ),
                    'line_height' => array(
                        'type' => 'slider',
                        'title' => __( 'Line-height', 'super-forms' ),
                        'min' => 1,
                        'max' => 50,
                        'step' => 1,
                        'units' => array(
                            'px', 
                            'em', 
                            'rem'
                        ),
                        'default' => array(
                            'value' => '12',
                            'unit' => 'px'
                        ),
                        'selector' => '.super-label',
                        'style_update' => 'lineHeight'
                    ),
                    'spacing' => array(
                        'type' => 'slider',
                        'title' =>  __( 'Letter spacing', 'super-forms' ),
                        'min' => -5,
                        'max' => 10,
                        'step' => 0.1,
                        'units' => array(
                            'px', 
                            'em', 
                            'rem'
                        ),
                        'default' => array(
                            'value' => '12',
                            'unit' => 'px'
                        ),
                        'selector' => '.super-label',
                        'style_update' => 'letterSpacing'
                    )
                )
            )
        )
    )
);