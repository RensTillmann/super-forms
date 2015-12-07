<?php
/*
$array['price_elements'] = array(
    'title' => __( 'Price Fields', 'super' ),   
    'class' => 'super-price-fields',
    'shortcodes' => array(
        'product' => array(
            'content' => ((!isset($content) || ($content=='')) ? '' : $content),
            'content_hidden' => true,
            'name' => __( 'Product', 'super' ),
            'icon' => 'gift',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='product'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Product'),
                        'label' => $label, 
                        'description'=>$description,
                        'tooltip' => $tooltip,
                        'currency' => array(
                            'name'=>__( 'Currency', 'super' ), 
                            'default'=> (!isset($attributes['currency']) ? __( '$', 'super' ) : $attributes['currency']),
                        ),
                        'price' => array(
                            'name'=>__( 'Price', 'super' ), 
                            'type'=>'slider',
                            'default'=> (!isset($attributes['price']) ? 25 : $attributes['price']),
                            'min'=>0,
                            'max'=>100,
                            'steps'=>1,
                        ),
                        'quantity' => array(
                            'name'=>__( 'Quantity', 'super' ), 
                            'desc'=>__( 'If you set to "1" the price will be shown without the quantity, unless you enabled "Custom Quantity by Users"', 'super' ),
                            'type'=>'slider',
                            'default'=> (!isset($attributes['quantity']) ? 1 : $attributes['quantity']),
                            'min'=>1,
                            'max'=>100,
                            'steps'=>1,
                        ),
                        'custom_quantity' => array(
                            'name'=>__( 'Custom Quantity by Users', 'super' ), 
                            'desc'=>__( 'You can allow users to enter their own quantity', 'super' ),
                            'type' => 'select',
                            'default'=> (!isset($attributes['custom_quantity']) ? 1 : $attributes['custom_quantity']),
                            'values' => array(
                                1 => __( 'Enabled (custom quantity)', 'super' ), 
                                0 => __( 'Disabled (fixed quantity)', 'super' ), 
                            ),
                        ),
                        'error' => $error,
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super' ),
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
            'name' => __( 'Total', 'super' ),
            'icon' => 'calculator',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='total'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Total'),
                        'label' => $label,
                        'description'=>$description,
                        'tooltip' => $tooltip,
                        'currency' => array(
                            'name'=>__( 'Currency', 'super' ), 
                            'default'=> (!isset($attributes['currency']) ? __( '$', 'super' ) : $attributes['currency']),
                        ),
                        'method' => array(
                            'name'=>__( 'Calculation method', 'super' ), 
                            'desc'=>__( 'Increase or Subtract values', 'super' ),
                            'type' => 'select',
                            'default'=> (!isset($attributes['method']) ? 'increase' : $attributes['method']),
                            'values' => array(
                                'increase' => __( 'Increase (add)', 'super' ), 
                                'subtract' => __( 'Subtract (deduct)', 'super' ), 
                            ),
                        ),
                        'fields' => array(
                            'name'=>__( 'Select specific fields to calculate', 'super' ), 
                            'desc'=>__( 'You can only select previously created fields.', 'super' ).'<br />'.__( 'Use CTRL or SHIFT to select multiple options.', 'super' ).'<br />'.__( 'If you leave this section blank all fields will be calculated together with the exception of discount fields, unless you have selected this field specifically to be calculated for this total. Make sure the fields you select only can contain numbers (int, float)', 'super' ),
                            'type' => 'previously_created_fields',
                            'multiple'=>true,
                            'default' => '',
                            'values' => array(),
                        ),
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super' ),
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
            'content' => ((!isset($content) || ($content=='')) ? '[super_shipping_item label="'.__( 'First choice', 'super' ).'" value="0.00"][super_shipping_item label="'.__( 'Second choice', 'super' ).'" value="0.00"][super_shipping_item label="'.__( 'Third choice', 'super' ).'" value="0.00"]' : $content),
            'content_hidden' => true,
            'name' => 'Shipping',
            'icon' => 'truck',
            'atts' => array(
                'general' => array(
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'shipping_items' => array(
                            'type' => 'shipping_items',
                            'default'=> ( !isset( $attributes['shipping_items'] ) ? 
                                array(
                                    array(
                                        'checked' => false,
                                        'label' => __( 'No shipping', 'super' ),
                                        'value' => 0.00
                                    ),
                                    array(
                                        'checked' => false,
                                        'label' => __( 'Shipping region 1', 'super' ),
                                        'value' => 7.95
                                    ),
                                    array(
                                        'checked' => false,
                                        'label' => __( 'Shipping region 2', 'super' ),
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
                            'name'=>__( 'Currency', 'super' ), 
                            'default'=> (!isset($attributes['currency']) ? __( '$', 'super' ) : $attributes['currency']),
                        ),
                        'validation' => $validation_empty,
                        'error' => $error,                  
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super' ),
                    'fields' => array(
                        'display' => array(
                            'name'=>__( 'Vertical / Horizontal display', 'super' ), 
                            'type' => 'select',
                            'default'=> (!isset($attributes['display']) ? 'vertical' : $attributes['display']),
                            'values' => array(
                                'vertical' => __( 'Vertical display ( | )', 'super' ), 
                                'horizontal' => __( 'Horizontal display ( -- )', 'super' ), 
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
                    'name' => __( 'Icon', 'super' ),
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
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'label' => array(
                            'name'=>__( 'Label', 'super' ),
                            'default'=> (!isset($attributes['label']) ? __( 'Label', 'super' ) : $attributes['label']),
                        ),
                        'value' => array(
                            'name'=>__( 'Value', 'super' ),
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
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'name' => SUPER_Shortcodes::name($attributes, $default='discount'),
                        'email' => SUPER_Shortcodes::email($attributes, $default='Discount'),
                        'label' => SUPER_Shortcodes::label($attributes, $default='Your discount:'),
                        'description' => SUPER_Shortcodes::description($attributes, $default='based on Total'),
                        'tooltip' => $tooltip,
                        'discount' => array(
                            'name'=>__( 'Discount in %', 'super' ), 
                            'desc'=>__( 'Discount in percentage (%)<br />Example: 25<br />Set to 0 to remove discount.', 'super' ), 
                            'type'=>'slider',
                            'default'=> (!isset($attributes['discount']) ? 10 : $attributes['discount']),
                            'min'=>0,
                            'max'=>100,
                            'steps'=>1,
                        ),
                        'currency' => array(
                            'name'=>__( 'Currency', 'super' ), 
                            'default'=> (!isset($attributes['currency']) ? __( '$', 'super' ) : $attributes['currency']),
                        ),
                        'method' => array(
                            'name'=>__( 'Calculation method', 'super' ), 
                            'desc'=>__( 'Increase or Subtract values', 'super' ),
                            'type' => 'select',
                            'default'=> (!isset($attributes['method']) ? 'increase' : $attributes['method']),
                            'values' => array(
                                'increase' => __( 'Increase (add)', 'super' ), 
                                'subtract' => __( 'Subtract (deduct)', 'super' ), 
                            ),
                        ),
                        'fields' => array(
                            'name'=>__( 'Discount calculated based on', 'super' ).':', 
                            'desc'=>__( 'You can only select previously created fields.', 'super' ).'<br />'.__( 'Use CTRL or SHIFT to select multiple options.', 'super' ).'<br />'.__( 'If you leave this section blank all fields will be calculated together. Make sure the fields you select only can contain numbers (int, float)', 'super' ),
                            'type' => 'previously_created_fields',
                            'multiple'=>true,
                            'default' => '',
                            'values' => array(),
                        ),
                    ),
                ),
                'advanced' => array(
                    'name' => __( 'Advanced', 'super' ),
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
                    'name' => __( 'General', 'super' ),
                    'fields' => array(
                        'barcode' => array(
                            'name'=>__( 'Barcode in numbers', 'super' ),
                            'desc'=>__( 'Example', 'super' ).': 12345670',
                            'default'=> (!isset($attributes['barcode']) ? '12345670' : $attributes['barcode']),
                            'required'=>true,   
                        ),
                        'barcodetype' => array(
                            'name'=>__( 'Barcode type', 'super' ), 
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
                            'name'=>__( 'Module Size', 'super' ), 
                            'type'=>'slider',
                            'default'=> (!isset($attributes['modulesize']) ? 10 : $attributes['modulesize']),
                            'min'=>5,
                            'max'=>30,
                            'steps'=>1,
                            'parent'=>'barcodetype',
                            'filter_value'=>'datamatrix',
                        ),
                        'quietzone' => array(
                            'name'=>__( 'Quiet Zone Modules', 'super' ), 
                            'type'=>'slider',
                            'default'=> (!isset($attributes['quietzone']) ? 1 : $attributes['quietzone']),
                            'min'=>1,
                            'max'=>10,
                            'steps'=>1,
                            'parent'=>'barcodetype',
                            'filter_value'=>'datamatrix',
                        ),
                        'rectangular' => array(
                            'name'=>__( 'Drawas rectangular', 'super' ), 
                            'type' => 'select',
                            'default'=> (!isset($attributes['rectangular']) ? 0 : $attributes['rectangular']),
                            'values' => array(
                                0 => __( 'Disabled', 'super' ),
                                1 => __( 'Enabled', 'super' ),
                            ),
                            'parent'=>'barcodetype',
                            'filter_value'=>'datamatrix',
                        ),
                        'barwidth' => array(
                            'name'=>__( 'Bar width in pixels', 'super' ), 
                            'type'=>'slider',
                            'default'=> (!isset($attributes['barwidth']) ? 1 : $attributes['barwidth']),
                            'min'=>1,
                            'max'=>5,
                            'steps'=>1,
                            'parent'=>'barcodetype',
                            'filter_value'=>'ean8,ean13,upc,std25,int25,code11,code39,code93,code128,codabar,msi'
                        ),
                        'barheight' => array(
                            'name'=>__( 'Bar height in pixels', 'super' ), 
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
                    'name' => __( 'Advanced', 'super' ),
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
                    'name' => __( 'General', 'super' ),
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