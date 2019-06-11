<?php
/*
$array['price_elements'] = array(
    'title' => esc_html__( 'Price Fields', 'super-forms' ),   
    'class' => 'super-price-fields',
    'shortcodes' => array(
        'product' => array(
            'content' => ((!isset($content) || ($content=='')) ? '' : $content),
            'content_hidden' => true,
            'name' => esc_html__( 'Product', 'super-forms' ),
            'icon' => 'gift',
            'atts' => array(
                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='product'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Product'),
                        'label' => $label, 
                        'description'=>$description,
                        'tooltip' => $tooltip,
                        'currency' => array(
                            'name'=>esc_html__( 'Currency', 'super-forms' ), 
                            'default'=> (!isset($attributes['currency']) ? esc_html__( '$', 'super-forms' ) : $attributes['currency']),
                        ),
                        'price' => array(
                            'name'=>esc_html__( 'Price', 'super-forms' ), 
                            'type'=>'slider',
                            'default'=> (!isset($attributes['price']) ? 25 : $attributes['price']),
                            'min'=>0,
                            'max'=>100,
                            'steps'=>1,
                        ),
                        'quantity' => array(
                            'name'=>esc_html__( 'Quantity', 'super-forms' ), 
                            'desc'=>esc_html__( 'If you set to "1" the price will be shown without the quantity, unless you enabled "Custom Quantity by Users"', 'super-forms' ),
                            'type'=>'slider',
                            'default'=> (!isset($attributes['quantity']) ? 1 : $attributes['quantity']),
                            'min'=>1,
                            'max'=>100,
                            'steps'=>1,
                        ),
                        'custom_quantity' => array(
                            'name'=>esc_html__( 'Custom Quantity by Users', 'super-forms' ), 
                            'desc'=>esc_html__( 'You can allow users to enter their own quantity', 'super-forms' ),
                            'type' => 'select',
                            'default'=> (!isset($attributes['custom_quantity']) ? 1 : $attributes['custom_quantity']),
                            'values' => array(
                                1 => esc_html__( 'Enabled (custom quantity)', 'super-forms' ), 
                                0 => esc_html__( 'Disabled (fixed quantity)', 'super-forms' ), 
                            ),
                        ),
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => esc_html__( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'maxlength' => $maxlength,
                        'minlength' => $minlength,
                        'width' => $width,
                        'exclude' => $exclude,
                        'error_position' => $error_position,
                        'styles' => $styles,
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'total' => array(
            'content' => ((!isset($content) || ($content=='')) ? '' : $content),
            'content_hidden' => true,
            'name' => esc_html__( 'Total', 'super-forms' ),
            'icon' => 'calculator',
            'atts' => array(
                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='total'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Total'),
                        'label' => $label,
                        'description'=>$description,
                        'tooltip' => $tooltip,
                        'currency' => array(
                            'name'=>esc_html__( 'Currency', 'super-forms' ), 
                            'default'=> (!isset($attributes['currency']) ? esc_html__( '$', 'super-forms' ) : $attributes['currency']),
                        ),
                        'method' => array(
                            'name'=>esc_html__( 'Calculation method', 'super-forms' ), 
                            'desc'=>esc_html__( 'Increase or Subtract values', 'super-forms' ),
                            'type' => 'select',
                            'default'=> (!isset($attributes['method']) ? 'increase' : $attributes['method']),
                            'values' => array(
                                'increase' => esc_html__( 'Increase (add)', 'super-forms' ), 
                                'subtract' => esc_html__( 'Subtract (deduct)', 'super-forms' ), 
                            ),
                        ),
                        'fields' => array(
                            'name'=>esc_html__( 'Select specific fields to calculate', 'super-forms' ), 
                            'desc'=>esc_html__( 'You can only select previously created fields.', 'super-forms' ).'<br />'.esc_html__( 'Use CTRL or SHIFT to select multiple options.', 'super-forms' ).'<br />'.esc_html__( 'If you leave this section blank all fields will be calculated together with the exception of discount fields, unless you have selected this field specifically to be calculated for this total. Make sure the fields you select only can contain numbers (int, float)', 'super-forms' ),
                            'type' => 'previously_created_fields',
                            'multiple'=>true,
                            'default' => '',
                            'values' => array(),
                        ),
                    ),
                ),
                'advanced' => array(
                    'name' => esc_html__( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'exclude' => $exclude,
                        'styles' => $styles,
                    ),
                ),                            
                'conditional_logic' => $conditional_logic_array
            ),
        ),

        'shipping' => array(
            'content' => ((!isset($content) || ($content=='')) ? '[super_shipping_item label="'.esc_html__( 'First choice', 'super-forms' ).'" value="0.00"][super_shipping_item label="'.esc_html__( 'Second choice', 'super-forms' ).'" value="0.00"][super_shipping_item label="'.esc_html__( 'Third choice', 'super-forms' ).'" value="0.00"]' : $content),
            'content_hidden' => true,
            'name' => 'Shipping',
            'icon' => 'truck',
            'atts' => array(
                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'shipping_items' => array(
                            'type' => 'shipping_items',
                            'default'=> ( !isset( $attributes['shipping_items'] ) ? 
                                array(
                                    array(
                                        'checked' => false,
                                        'label' => esc_html__( 'No shipping', 'super-forms' ),
                                        'value' => 0.00
                                    ),
                                    array(
                                        'checked' => false,
                                        'label' => esc_html__( 'Shipping region 1', 'super-forms' ),
                                        'value' => 7.95
                                    ),
                                    array(
                                        'checked' => false,
                                        'label' => esc_html__( 'Shipping region 2', 'super-forms' ),
                                        'value' => 15.00
                                    )
                                ) : $attributes['shipping_items']
                            ),
                        ),
                        'name' => SUPER_Shortcodes::name($attributes, $default='shipping'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Shipping'),
                        'label' => $label, 
                        'description'=>$description,
                        'tooltip' => $tooltip,
                        'currency' => array(
                            'name'=>esc_html__( 'Currency', 'super-forms' ), 
                            'default'=> (!isset($attributes['currency']) ? esc_html__( '$', 'super-forms' ) : $attributes['currency']),
                        ),
                        'validation' => $validation_empty,
                        'error' => $error,                  
                    ),
                ),
                'advanced' => array(
                    'name' => esc_html__( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'display' => array(
                            'name'=>esc_html__( 'Vertical / Horizontal display', 'super-forms' ), 
                            'type' => 'select',
                            'default'=> (!isset($attributes['display']) ? 'vertical' : $attributes['display']),
                            'values' => array(
                                'vertical' => esc_html__( 'Vertical display ( | )', 'super-forms' ), 
                                'horizontal' => esc_html__( 'Horizontal display ( -- )', 'super-forms' ), 
                            ),
                        ),
                        'grouped' => $grouped,                    
                        'width' => $width,
                        'exclude' => $exclude, 
                        'error_position' => $error_position_left_only,
                        'styles' => $styles,
                    ),
                ),
                'icon' => array(
                    'name' => esc_html__( 'Icon', 'super-forms' ),
                    'fields' => array(
                        'icon_position' => $icon_position,
                        'icon_align' => $icon_align,
                        'icon' => SUPER_Shortcodes::icon($attributes,'truck'),
                    ),
                ),
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'shipping_item' => array(
            'hidden' => true,
            'name' => '',
            'icon' => 'section-width',
            'atts' => array(
                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'label' => array(
                            'name'=>esc_html__( 'Label', 'super-forms' ),
                            'default'=> (!isset($attributes['label']) ? esc_html__( 'Label', 'super-forms' ) : $attributes['label']),
                        ),
                        'value' => array(
                            'name'=>esc_html__( 'Value', 'super-forms' ),
                            'default'=> (!isset($attributes['value']) ? '0.00' : $attributes['value']),
                        ),                        
                    ),
                ),
            ),
        ),
        'discount' => array(
            'content' => ((!isset($content) || ($content=='')) ? '' : $content),
            'content_hidden' => true,
            'name' => 'Discount',
            'icon' => 'tag',
            'atts' => array(
                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='discount'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Discount'),
                        'label' => SUPER_Shortcodes::label($attributes, $default='Your discount:'),
                        'description' => SUPER_Shortcodes::description($attributes, $default='based on Total'),
                        'tooltip' => $tooltip,
                        'discount' => array(
                            'name'=>esc_html__( 'Discount in %', 'super-forms' ), 
                            'desc'=>esc_html__( 'Discount in percentage (%)<br />Example: 25<br />Set to 0 to remove discount.', 'super-forms' ), 
                            'type'=>'slider',
                            'default'=> (!isset($attributes['discount']) ? 10 : $attributes['discount']),
                            'min'=>0,
                            'max'=>100,
                            'steps'=>1,
                        ),
                        'currency' => array(
                            'name'=>esc_html__( 'Currency', 'super-forms' ), 
                            'default'=> (!isset($attributes['currency']) ? esc_html__( '$', 'super-forms' ) : $attributes['currency']),
                        ),
                        'method' => array(
                            'name'=>esc_html__( 'Calculation method', 'super-forms' ), 
                            'desc'=>esc_html__( 'Increase or Subtract values', 'super-forms' ),
                            'type' => 'select',
                            'default'=> (!isset($attributes['method']) ? 'increase' : $attributes['method']),
                            'values' => array(
                                'increase' => esc_html__( 'Increase (add)', 'super-forms' ), 
                                'subtract' => esc_html__( 'Subtract (deduct)', 'super-forms' ), 
                            ),
                        ),
                        'fields' => array(
                            'name'=>esc_html__( 'Discount calculated based on', 'super-forms' ).':', 
                            'desc'=>esc_html__( 'You can only select previously created fields.', 'super-forms' ).'<br />'.esc_html__( 'Use CTRL or SHIFT to select multiple options.', 'super-forms' ).'<br />'.esc_html__( 'If you leave this section blank all fields will be calculated together. Make sure the fields you select only can contain numbers (int, float)', 'super-forms' ),
                            'type' => 'previously_created_fields',
                            'multiple'=>true,
                            'default' => '',
                            'values' => array(),
                        ),
                    ),
                ),
                'advanced' => array(
                    'name' => esc_html__( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'exclude' => $exclude,
                        'styles' => $styles,
                    ),
                ),                            
                'conditional_logic' => $conditional_logic_array
            ),
        ),
        'barcode' => array(
            'content' => ((!isset($content) || ($content=='')) ? '' : $content),
            'content_hidden' => true,
            'name' => 'Barcode',
            'icon' => 'barcode',
            'atts' => array(
                'general' => array(
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'barcode' => array(
                            'name'=>esc_html__( 'Barcode in numbers', 'super-forms' ),
                            'desc'=>esc_html__( 'Example', 'super-forms' ).': 12345670',
                            'default'=> (!isset($attributes['barcode']) ? '12345670' : $attributes['barcode']),
                            'required'=>true,   
                        ),
                        'barcodetype' => array(
                            'name'=>esc_html__( 'Barcode type', 'super-forms' ), 
                            'type' => 'select',
                            'default'=> (!isset($attributes['barcodetype']) ? 'ean8' : $attributes['barcodetype']),
                            'filter'=>true,
                            'values' => array(
                                'ean8' => 'EAN 8',
                                'ean13' => 'EAN 13',
                                'upc' => 'UPC',
                                'std25' => 'standard 2 of 5 (industrial)',
                                'int25' => 'interleaved 2 of 5',
                                'code11' => 'code 11',
                                'code39' => 'code 39',
                                'code93' => 'code 93',
                                'code128' => 'code 128',
                                'codabar' => 'codabar',
                                'msi' => 'MSI',
                                'datamatrix' => 'Data Matrix',
                            ),
                        ),
                        'name' => SUPER_Shortcodes::name($attributes, $default='barcode'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Barcode'),
                        'label' => $label,
                        'description'=>$description,
                        'tooltip' => $tooltip,
                        'background' => array(
                            'name'=>'Background color',
                            'desc'=>'Choose a custom background color.',
                            'default'=> (!isset($attributes['background']) ? '#FFFFFF' : $attributes['background']),
                            'type'=>'color',
                        ),
                        'barcolor' => array(
                            'name'=>'Bar color',
                            'desc'=>'Choose a custom bar color.',
                            'default'=> (!isset($attributes['barcolor']) ? '#000000' : $attributes['barcolor']),
                            'type'=>'color',
                        ), 
                        'modulesize' => array(
                            'name'=>esc_html__( 'Module Size', 'super-forms' ), 
                            'type'=>'slider',
                            'default'=> (!isset($attributes['modulesize']) ? 10 : $attributes['modulesize']),
                            'min'=>5,
                            'max'=>30,
                            'steps'=>1,
                            'parent'=>'barcodetype',
                            'filter_value'=>'datamatrix',
                        ),
                        'quietzone' => array(
                            'name'=>esc_html__( 'Quiet Zone Modules', 'super-forms' ), 
                            'type'=>'slider',
                            'default'=> (!isset($attributes['quietzone']) ? 1 : $attributes['quietzone']),
                            'min'=>1,
                            'max'=>10,
                            'steps'=>1,
                            'parent'=>'barcodetype',
                            'filter_value'=>'datamatrix',
                        ),
                        'rectangular' => array(
                            'name'=>esc_html__( 'Drawas rectangular', 'super-forms' ), 
                            'type' => 'select',
                            'default'=> (!isset($attributes['rectangular']) ? 0 : $attributes['rectangular']),
                            'values' => array(
                                0 => esc_html__( 'Disabled', 'super-forms' ),
                                1 => esc_html__( 'Enabled', 'super-forms' ),
                            ),
                            'parent'=>'barcodetype',
                            'filter_value'=>'datamatrix',
                        ),
                        'barwidth' => array(
                            'name'=>esc_html__( 'Bar width in pixels', 'super-forms' ), 
                            'type'=>'slider',
                            'default'=> (!isset($attributes['barwidth']) ? 1 : $attributes['barwidth']),
                            'min'=>1,
                            'max'=>5,
                            'steps'=>1,
                            'parent'=>'barcodetype',
                            'filter_value'=>'ean8,ean13,upc,std25,int25,code11,code39,code93,code128,codabar,msi'
                        ),
                        'barheight' => array(
                            'name'=>esc_html__( 'Bar height in pixels', 'super-forms' ), 
                            'type'=>'slider',
                            'default'=> (!isset($attributes['barheight']) ? 50 : $attributes['barheight']),
                            'min'=>10,
                            'max'=>100,
                            'steps'=>1,
                            'parent'=>'barcodetype',
                            'filter_value'=>'ean8,ean13,upc,std25,int25,code11,code39,code93,code128,codabar,msi'
                        ),
                    ),
                ),
                'advanced' => array(
                    'name' => esc_html__( 'Advanced', 'super-forms' ),
                    'fields' => array(
                        'grouped' => $grouped,
                        'exclude' => $exclude,
                        'styles' => $styles,
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
                    'name' => esc_html__( 'General', 'super-forms' ),
                    'fields' => array(
                        'conditional_field' => $conditional_field_name,
                        'conditional_logic' => $conditional_logic,
                        'conditional_value' => $conditional_field_value,
                    ),
                ),
            ),
        ),


    )
);
*/