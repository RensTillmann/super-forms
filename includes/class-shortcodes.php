<?php
/**
 * Super Forms Common Class.
 *
 * @author      feeling4design
 * @category    Class
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Shortcodes
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'SUPER_Shortcodes' ) ) :

/**
 * SUPER_Shortcodes
 */
class SUPER_Shortcodes {
        
    /** 
     *  All the fields
     *
     *  Create an array with all the fields
     *
     *  @since      1.0.0
    */
    public static function shortcodes( $shortcode=false, $attributes=false, $content=false ) {
        
        $attributes = stripslashes_deep($attributes);

        $attr = array( 
            'shortcode'=>$shortcode, 
            'attributes'=>$attributes, 
            'content'=>$content 
        );
            
        include( 'shortcodes/predefined-arrays.php' );
        
        $array = array();
        
        $array = apply_filters( 'super_shortcodes_start_filter', $array, $attr );
        
        /** 
         *  Layout Elements
         *
         *  @since      1.0.0
        */
        include( 'shortcodes/layout-elements.php' );
        $array = apply_filters( 'super_shortcodes_after_layout_elements_filter', $array, $attr );

        
        /** 
         *  Form Elements
         *
         *  @since      1.0.0
        */
        include( 'shortcodes/form-elements.php' );
        $array = apply_filters( 'super_shortcodes_after_form_elements_filter', $array, $attr );
        
        
        /** 
         *  Price Elements
         *
         *  @since      1.0.0
        */
        include( 'shortcodes/price-elements.php' );
        $array = apply_filters( 'super_shortcodes_after_price_elements_filter', $array, $attr );
        
        $array = apply_filters( 'super_shortcodes_end_filter', $array, $attr );

        return $array;
        
    }
    
    /** 
     *  Output the element HTML on the builder page (create form) inside the preview area
     *
     * @param  string  $tag
     *
     *  @since      1.0.0
    */
    public static function output_builder_html( $tag, $group, $data, $inner, $shortcodes=null, $settings=null ) {
        
        if( $shortcodes==null ) {
            $shortcodes = self::shortcodes();
        }

        if( !isset( $shortcodes[$group]['shortcodes'][$tag] ) ) {
            return '';
        }

        $name = $shortcodes[$group]['shortcodes'][$tag]['name'];

        if(count($data)==0){
            $fields = array();
            foreach( $shortcodes[$group]['shortcodes'][$tag]['atts'] as $k => $v ) {
                if( isset( $v['fields'] ) ) {
                    foreach( $v['fields'] as $vk => $vv ) {
                        if( ($vv['default']!=='') && ($vv['default']!==0) ) {
                            $fields[$vk] = $vv['default'];
                        }
                    }
                }
            }
            $data = $fields;
        }else{
            $data = $data;
        }

        $data = json_decode(json_encode($data), true);
        $inner = json_decode(json_encode($inner), true);
   
        $class = '';
        $inner_class = '';

        if($tag=='column'){
            $sizes = array(
                '1/5'=>array('one_fifth',20),
                '1/4'=>array('one_fourth',25),
                '1/3'=>array('one_third',33.3333334),
                '2/5'=>array('two_fifth',40),
                '1/2'=>array('one_half',50),
                '3/5'=>array('three_fifth',60),
                '2/3'=>array('two_third',66.6666667),
                '3/4'=>array('three_fourth',75),
                '4/5'=>array('four_fifth',80),
                '1/1'=>array('one_full',100),
            ); 
            $class .= ' super_'.$sizes[$data['size']][0] . ' super-' . str_replace( 'column_', 'super_', $tag );
        }
        if($tag=='multipart'){
            $class .= ' ' . $tag;
        }
  
        if( isset( $shortcodes[$group]['shortcodes'][$tag]['drop'] ) ) {
            $class .= ' drop-here';
            $inner_class .= ' super-dropable';
        }
        
        $result = '';
        $result .= '<div class="super-element' . $class . '" data-shortcode-tag="' . $tag . '" data-group="'.$group.'" ' . ( $tag=='column' ? 'data-size="' . $data['size'] . '"' : '' ) . '>';
            $result .= '<div class="super-element-header">';
                if($tag=='column'){
                    $result .= '<div class="resize popup" data-content="Change Column Size">';
                        $result .= '<span class="smaller"><i class="fa fa-angle-left"></i></span>';
                        $result .= '<span class="current">' . $data['size'] . '</span><span class="bigger"><i class="fa fa-angle-right"></i></span>';
                    $result .= '</div>';
                }else{
                    $result .= '<div class="super-title">' . $name . '</div>';
                }
                $result .= '<div class="super-element-actions">';
                    $result .= '<span class="edit popup" data-placement="top" title="" data-original-title="Edit element"><i class="fa fa-pencil"></i></span>';
                    $result .= '<span class="duplicate popup" data-placement="top" title="" data-original-title="Duplicate element"><i class="fa fa-files-o"></i></span>';
                    $result .= '<span class="move popup" data-placement="top" title="" data-original-title="Reposition element"><i class="fa fa-arrows"></i></span>';
                    //$result .= '<span class="shortcode popup" data-placement="top" title="" data-original-title="Copy shortcode"><i class="fa fa-code"></i></span>';
                    $result .= '<span class="delete popup" data-placement="top" title="" data-original-title="Delete"><i class="fa fa-times"></i></span>';
                $result .= '</div>';
            $result .= '</div>';
            $result .= '<div class="super-element-inner' . $inner_class . '">';
                if( ( $tag!='column' ) && ( $tag!='multipart' ) ) {
                    if( $tag=='button' ) $GLOBALS['super_found_button'] = true;
                    $result .= self::output_element_html( $tag, $group, $data, $inner, $shortcodes, $settings );
                }
                if( !empty( $inner ) ) {
                    foreach( $inner as $k => $v ) {
                        $result .= self::output_builder_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings );
                    }
                }
            $result .= '</div>';
            $result .= '<textarea name="element-data">' . json_encode( $data ) . '</textarea>';
        $result .= '</div>';
        
        return $result;
        
    }


    /**
     *  Output the max and min length data attributes that are used for every element
     *
     * @param  nummeric     $maxlength
     * @param  nummeric     $minlength
     * @param  string       $tag
     *
     *  @since      1.0.0
     */
    public static function field_data_attribute_max_min( $maxlength, $minlength, $tag ) {
        $result = '';
        if( $tag=='file' ) {
            if( $minlength>0 ) $result .= ' data-minfiles="' . $minlength . '"';
            if( $maxlength>0 ) $result .= ' data-maxfiles="' . $maxlength . '"';
        }elseif( $tag=='product' ) {
            if( $maxlength>0 ) $result .= ' max="' . $maxlength . '" data-maxlength="' . $maxlength . '"';
            $result .= ' min="'.$minlength.'" data-minlength="' . $minlength . '"';
        }else{
            if( $maxlength>0 ) $result .= ' data-maxlength="' . $maxlength . '"';
            if( $minlength>0 ) $result .= ' data-minlength="' . $minlength . '"';
            if( $maxnumber>0 ) $result .= ' data-maxnumber="' . $maxnumber . '"';
            if( $minnumber>0 ) $result .= ' data-minnumber="' . $minnumber . '"';
        }
        return $result;
    }


    public static function opening_tag( $tag, $atts, $class='', $styles='' ) {        
        $style = '';
        if($tag=='divider') $atts['width'] = 0;
        if( !isset( $atts['width'] ) ) $atts['width'] = 0;
        if( $atts['width']!=0 ) $style .= 'width:' . $atts['width'] . 'px;';
        if( !empty( $atts['tooltip'] ) ) {
            wp_enqueue_style('super-tooltips', SUPER_PLUGIN_FILE.'assets/css/backend/tooltips.min.css', array(), SUPER_VERSION);    
            wp_enqueue_script('super-tooltips', SUPER_PLUGIN_FILE.'assets/js/backend/tooltips.min.js', array(), SUPER_VERSION);   
        }
        $result = '<div';
        if( ( $style!='' ) || ( $styles!='' ) ) $result .= ' style="' . $style . $styles . '"';
        $result .= ' class="super-shortcode super-field super-' . $tag;

        $align = '';
        if( isset( $atts['align'] ) ) $align = ' super-align-' . $atts['align'];
        $result .= $align;

        if( !empty( $atts['tooltip'] ) ) $result .= ' popup';
        if( !isset( $atts['error_position'] ) ) $atts['error_position'] = '';
        $result .= ' ' . $atts['error_position'];
        //if(($tag=='super_checkbox') || ($tag=='super_radio') || ($tag=='super_shipping')) $result .= ' display-'.$display;
        if( !isset( $atts['grouped'] ) ) $atts['grouped'] = 0;
        if($atts['grouped']==0) $result .= ' ungrouped ';
        if($atts['grouped']==1) $result .= ' grouped ';
        if($atts['grouped']==2) $result .= ' grouped grouped-end ';
        $result .= ' ' . $class . '"';
        if( !empty( $atts['tooltip'] ) ) $result .= ' title="' . $atts['tooltip'] . '" data-placement="top"';
        $result .= self::conditional_attributes( $atts );
        $result .= '>';
        if( !isset( $atts['label'] ) ) $atts['label'] = '';
        if( $atts['label']!='' ) {
            $result .= self::field_label( $atts['label'] );
        }
        if( !isset( $atts['description'] ) ) $atts['description'] = '';
        if( $atts['description']!='' ) {
            $result .= self::field_description( $atts['description'] );
        }
        return $result;
    }
    public static function conditional_attributes( $atts ) {        
        if( !isset( $atts['conditional_action'] ) ) $atts['conditional_action'] = 'disabled';
        if( !isset( $atts['conditional_trigger'] ) ) $atts['conditional_trigger'] = 'all';
        if( $atts['conditional_action']!='disabled' ) {
            return ' data-conditional_action="' . $atts['conditional_action'] . '" data-conditional_trigger="' . $atts['conditional_trigger'] . '"';
        }
    }
    public static function field_label( $label ) {        
        return '<div class="super-label">' . $label . '</div>';
    }
    public static function field_description( $description ) {        
        return '<div class="super-description">' . $description . '</div>';
    }        
    public static function opening_wrapper( $atts ) {
        if( !isset( $atts['icon'] ) ) $atts['icon'] = '';
        if( !isset( $atts['icon_position'] ) ) $atts['icon_position'] = 'outside';
        if( !isset( $atts['icon_align'] ) ) $atts['icon_align'] = 'left';
        $result = '<div class="super-field-wrapper ' . ( $atts['icon']!='' ? 'super-icon-' . $atts['icon_position'] . ' super-icon-' . $atts['icon_align'] : '' ) . '">';
        if($atts['icon']!=''){
            $result .= '<i class="fa fa-'.$atts['icon'].' super-icon"></i>';
        }
        return $result;
    }
    public static function common_attributes( $atts, $tag ) {        
        if( !isset( $atts['error'] ) ) $atts['error'] = '';
        if( !isset( $atts['validation'] ) ) $atts['validation'] = '';
        if( !isset( $atts['conditional_validation'] ) ) $atts['conditional_validation'] = '';
        if( !isset( $atts['conditional_validation_value'] ) ) $atts['conditional_validation_value'] = '';
        if( !isset( $atts['may_be_empty'] ) ) $atts['may_be_empty'] = 'false';
        if( !isset( $atts['email'] ) ) $atts['email'] = '';
        if( !isset( $atts['exclude'] ) ) $atts['exclude'] = 0;
        if( !isset( $atts['maxlength'] ) ) $atts['maxlength'] = 0;
        if( !isset( $atts['minlength'] ) ) $atts['minlength'] = 0;
        $result = ' data-message="' . $atts['error'] . '" data-validation="'.$atts['validation'].'" data-may-be-empty="'.$atts['may_be_empty'].'" data-conditional-validation="'.$atts['conditional_validation'].'" data-conditional-validation-value="'.$atts['conditional_validation_value'].'" data-email="'.$atts['email'].'" data-exclude="'.$atts['exclude'].'"';
        if( !empty( $atts['placeholder'] ) ) {
            $result .= ' placeholder="' . $atts['placeholder'] . '"';
        }
        if($tag=='file'){
            if( $atts['minlength']>0 ) {
                $result .= ' data-minfiles="' . $atts['minlength'] . '"';
            }
            if( $atts['maxlength']>0 ) {
                $result .= ' data-maxfiles="' . $atts['maxlength'] . '"';
            }
        }elseif( $tag=='product' ) {
            if( $atts['maxlength']>0 ) {
                $result .= ' max="' . $atts['maxlength'] . '" data-maxlength="' . $atts['maxlength'] . '"';
            }
            $result .= ' min="' . $atts['minlength'] . '" data-minlength="' . $atts['minlength'] . '"';
        }else{
            if($tag=='date'){
                if( $atts['maxlength']!=0 ) {
                    $result .= ' data-maxlength="' . $atts['maxlength'] . '"';
                }
                if( $atts['minlength']!=0 ) {
                    $result .= ' data-minlength="' . $atts['minlength'] . '"';
                }
            }else{
                if( $atts['maxlength']>0 ) {
                    $result .= ' data-maxlength="' . $atts['maxlength'] . '"';
                }
                if( $atts['minlength']>0 ) {
                    $result .= ' data-minlength="' . $atts['minlength'] . '"';
                }
            }

            if( !isset( $atts['maxnumber'] ) ) $atts['maxnumber'] = 0;
            if( !isset( $atts['minnumber'] ) ) $atts['minnumber'] = 0;
            if( $atts['maxnumber']>0 ) {
                $result .= ' data-maxnumber="' . $atts['maxnumber'] . '"';
            }
            if( $atts['minnumber']>0 ) {
                $result .= ' data-minnumber="' . $atts['minnumber'] . '"';
            }
        }
        return $result;
    }
    public static function loop_conditions( $atts ) {
        if( !isset( $atts['conditional_action'] ) ) $atts['conditional_action'] = 'disabled';
        if( !isset( $atts['conditional_items'] ) ) $atts['conditional_items'] = '';
        if( ( $atts['conditional_items']!=null ) && ( $atts['conditional_action']!='disabled' ) ) {
            $items = '';
            foreach( $atts['conditional_items'] as $k => $v ) {
                $items .= '<div hidden class="super-conditional-logic" data-field="' . $v['field'] . '" data-logic="' . $v['logic'] . '" data-value="' . $v['value'] . '"></div>';
            }
            return $items;
        }
    }


    /** 
     *  Callback functions for each element to output the HTML
     *
     * @param  string  $tag
     * @param  string  $group
     * @param  array   $data
     *
     *  @since      1.0.0
    */
    public static function multipart( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
        $result  = '';
        $result .= '<div class="super-shortcode super-' . $tag . '" data-step-name="' . $atts['step_name'] .'" data-step-description="' . $atts['step_description'] . '" data-icon="' . $atts['icon'] . '">';
        if( !empty( $inner ) ) {
            // Before doing the actuall loop we need to know how many columns this form contains
            // This way we can make sure to correctly close the column system
            $GLOBALS['super_column_found'] = 0;
            foreach( $inner as $k => $v ) {
                if( $v['tag']=='column' ) $GLOBALS['super_column_found']++;
            }
            foreach( $inner as $k => $v ) {
                $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings );
            }
        }
        $result .= '</div>';
        return $result;
    }


    /** 
     *  Opens and closes the grid system based on the method
     *
     * @param  array  $atts
     * @param  array  $grid
     * @param  array  $sizes
     * @param  string  $method
     *
     *  @since      1.2.2
    */
    public static function open_close_grid( $method='', $atts=array(), $grid=array(), $sizes=array() ) {
        $result = '';
        if( $method=='open' ) {
            // Lets open a new grid
            $result .= '<div class="super-grid super-shortcode">';
        }
        if( $method=='close' ) {
            // Lets close the grid
            $result .= '</div>';
        }
        if( $method=='close_width' ) {
            // It might happen that the columns together are to much in width to put inside a grid
            // If this is the case make sure we first close the grid
            $width = floor($grid['width']+$sizes[$atts['size']][1]);
            if( $width>100 ) {

                // Lets close the grid
                $result .= '</div>';

                // Lets open the new grid  
                $result .= '<div class="super-grid super-shortcode">';
                $grid['width'] = 0;        
                $grid['columns']['current'] = 0;        
            }
            $GLOBALS['super_grid_system'] = $grid;
        }
        return $result;
    }


    /** 
     *  Opens and closes the columns inside the grid system
     *
     * @param  array  $atts
     * @param  array  $inner
     * @param  string $class
     * @param  array  $grid
     * @param  array  $sizes
     * @param  array  $shortcodes
     * @param  array  $settings
     *
     *  @since      1.2.2
    */
    public static function open_close_column( $atts=array(), $inner=array(), $class='', $grid=array(), $sizes=array(), $shortcodes=array(), $settings=array() ) {
        $result = '';

        // Instantly open our very first column
        //$grid['columns']['current']++;
        //$grid['width'] = $grid['width']+$sizes[$atts['size']][1];
        //$GLOBALS['super_grid_system'] = $grid;
        
        // Output the column and it's inner content
        $result .= '<div class="super-shortcode super_' . $sizes[$atts['size']][0] . ' super-column column-number-'.$grid['columns']['current'].' grid-level-'.$grid['level'].' ' . $class . ' ' . $atts['margin'] . '"'; 
        $result .= self::conditional_attributes( $atts );
        $result .= '>';
        if( !empty( $inner ) ) {
            $grid['level']++;
            $own_width = $grid['width'];
            $own_current = $grid['columns']['current'];
            $grid['width']=0;
            $grid['columns']['current']=0;
            $GLOBALS['super_grid_system'] = $grid;
            foreach( $inner as $k => $v ) {
                if( $v['tag']=='button' ) $GLOBALS['super_found_button'] = true;
                $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings );
            }
            $grid['level']--;
            $grid['width']=$own_width;
            $grid['columns']['current']=$own_current;
            $GLOBALS['super_grid_system'] = $grid;
        }
        $result .= '</div>';
        
        return $result;
    }

    public static function column( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
        $result  = '';
        $sizes = array(
            '1/5'=>array('one_fifth',20),
            '1/4'=>array('one_fourth',25),
            '1/3'=>array('one_third',33.3333334),
            '2/5'=>array('two_fifth',40),
            '1/2'=>array('one_half',50),
            '3/5'=>array('three_fifth',60),
            '2/3'=>array('two_third',66.6666667),
            '3/4'=>array('three_fourth',75),
            '4/5'=>array('four_fifth',80),
            '1/1'=>array('one_full',100),
        );

        // @since   1.1.7    - make sure this data is set
        if( !isset( $atts['duplicate'] ) ) $atts['duplicate'] = '';

        // Before setting the global grid system variable count the inner columns
        $inner_total = 0;
        if( !empty( $inner ) ) {
            foreach( $inner as $k => $v ) {
                if( $v['tag']=='column' ) $inner_total++;
            }
        }

        if( !isset( $GLOBALS['super_grid_system'] ) ) {
            $GLOBALS['super_grid_system'] = array(
                'level' => 0,
                'width' => 0,
                'columns' => array(
                    'current' => 0,
                    'total' => $GLOBALS['super_column_found'],
                    'inner_total' => $inner_total
                )
            );
            unset($GLOBALS['super_column_found']);
        }
        $grid = $GLOBALS['super_grid_system'];

        // This is the first column of the grid
        if( ( $grid['level']==0 ) && ( $grid['columns']['current']==0 ) ) {
            
            $result .= self::open_close_grid( 'open' );
            
            // Instantly open our very first column
            $grid['columns']['current']++;
            $grid['width'] = $grid['width']+$sizes[$atts['size']][1];
            $GLOBALS['super_grid_system'] = $grid;

            $result .= self::open_close_column( $atts, $inner, 'first-column', $grid, $sizes, $shortcodes, $settings );

            // If this is the only column in this form, and we couldn't find any inner columns we can close the grid
            if( $grid['columns']['total']==1 ) {
                $result .= self::open_close_grid( 'close' );
            }

        }else{

            // If we are in a inner grid
            if( $grid['level']>0 ) {

                $class = '';
                // This is the first column of the grid
                if( $grid['columns']['current']==0 ) {
                    $class = 'first-column';
                    $result .= self::open_close_grid( 'open' );
                }else{
                    $result .= self::open_close_grid( 'close_width', $atts, $grid, $sizes );
                    $grid = $GLOBALS['super_grid_system'];
                }
                // Seems like this is the last column
                if( $grid['columns']['inner_total']>1 ) {
                    if( $grid['columns']['inner_total']==$grid['columns']['current'] ) {
                        $class = 'last-column';
                    }
                }

                // Instantly open our very first column
                $grid['columns']['current']++;
                $grid['width'] = $grid['width']+$sizes[$atts['size']][1];
                $GLOBALS['super_grid_system'] = $grid;

                $result .= self::open_close_column( $atts, $inner, $class, $grid, $sizes, $shortcodes, $settings );

                // If this is the only inner column inside this grid we can close the grid
                if( $grid['columns']['inner_total']==$grid['columns']['current'] ) {
                    $result .= self::open_close_grid( 'close' );
                }

            }else{

                // We are either already inside a grid, or we are drawing the other columns inside the grid
                if( $grid['columns']['current']>0 ) {

                    $result .= self::open_close_grid( 'close_width', $atts, $grid, $sizes );
                    $grid = $GLOBALS['super_grid_system'];

                    $class = '';
                    if( $grid['columns']['current']==0 ) {
                        $class = 'first-column';
                    }
                    // Check if we are in an inner grid
                    if( $grid['level']>0 ) {
                        // Seems like this is the last column
                        if( $grid['columns']['inner_total']>1 ) {
                            if( $grid['columns']['inner_total']==$grid['columns']['current'] ) {
                                $class = 'last-column';
                            }
                        }
                    }else{
                        // Seems like this is the last column
                        if( $grid['columns']['total']>1 ) {
                            if( $grid['columns']['total']==$grid['columns']['current'] ) {
                                $class = 'last-column';
                            }
                        }
                    }


                    // Instantly open our very first column
                    $grid['columns']['current']++;
                    $grid['width'] = $grid['width']+$sizes[$atts['size']][1];
                    $GLOBALS['super_grid_system'] = $grid;
                
                    $result .= self::open_close_column( $atts, $inner, $class, $grid, $sizes, $shortcodes, $settings );

                    // Check if we are in an inner grid
                    if( $grid['level']>0 ) {
                        // If this is the only column in this form, and we couldn't find any inner columns we can close the grid
                        if( $grid['columns']['inner_total']==$grid['columns']['current'] ) {
                            $result .= self::open_close_grid( 'close' );
                        }
                    }else{
                        // If this is the only column in this form, and we couldn't find any inner columns we can close the grid
                        if( $grid['columns']['total']==$grid['columns']['current'] ) {
                            $result .= self::open_close_grid( 'close' );
                        }
                    }
                }
            }
        }

        // Lets make sure to remove the global settings before returning the grid
        unset($GLOBALS['super_column_found']);

        // Now return the grid
        return $result;

    }
    public static function text( $tag, $atts ) {
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts );
        $result .= '<input class="super-shortcode-field" type="text"';

        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }

        // @since   1.0.6    - make sure this data is set
        if( ( !isset( $atts['value'] ) ) || ( $atts['value']=='' ) ) {
            $atts['value'] = '';
        }else{
            $atts['value'] = SUPER_Common::email_tags( $atts['value'] );
        }

        $result .= ' name="' . $atts['name'] . '" value="' . $atts['value'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function textarea( $tag, $atts ) {
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts );
        
        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }

        // @since   1.0.6    - make sure this data is set
        if( ( !isset( $atts['value'] ) ) || ( $atts['value']=='' ) ) {
            $atts['value'] = '';
        }else{
            $atts['value'] = SUPER_Common::email_tags( $atts['value'] );
        }
        $result .= '<textarea class="super-shortcode-field"';
        $result .= ' name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />' . $atts['value'] . '</textarea>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function dropdown( $tag, $atts ) {
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts );
        
        $multiple = '';
        if( !isset( $atts['maxlength'] ) ) $atts['maxlength'] = 0;
        if( !isset( $atts['minlength'] ) ) $atts['minlength'] = 0;
        if( ($atts['minlength']>1) || ($atts['maxlength']>1) ) $multiple = ' multiple';

        $items = array();
        $placeholder = '';
        
        // @since   1.0.6
        if( !isset( $atts['retrieve_method'] ) ) $atts['retrieve_method'] = 'custom';
        if($atts['retrieve_method']=='custom') {
            
            foreach( $atts['dropdown_items'] as $k => $v ) {
                if( ( $v['checked']===true ) || ( $v['checked']==='true' ) ) {
                    if( $placeholder=='' ) {
                        $placeholder .= $v['label'];
                    }else{
                        $placeholder .= ', ' . $v['label'];
                    }
                    $items[] = '<li data-value="' . esc_attr( $v['value'] ) . '" class="selected">' . $v['label'] . '</li>'; 
                }else{
                    $items[] = '<li data-value="' . esc_attr( $v['value'] ) . '">' . $v['label'] . '</li>'; 
                }
            }
        }

        // @since   1.0.6
        if($atts['retrieve_method']=='taxonomy') {
            if( !isset( $atts['retrieve_method_taxonomy'] ) ) $atts['retrieve_method_taxonomy'] = 'category';
            if( !isset( $atts['retrieve_method_exclude_taxonomy'] ) ) $atts['retrieve_method_exclude_taxonomy'] = '';
            if( !isset( $atts['retrieve_method_hide_empty'] ) ) $atts['retrieve_method_hide_empty'] = 0;
            if( !isset( $atts['retrieve_method_parent'] ) ) $atts['retrieve_method_parent'] = '';
            
            $args = array(
                'hide_empty' => $atts['retrieve_method_hide_empty'],
                'exclude' => $atts['retrieve_method_exclude_taxonomy'],
                'taxonomy' => $atts['retrieve_method_taxonomy'],
                'parent' => $atts['retrieve_method_parent'],
            );
            $categories = get_categories( $args );
            foreach( $categories as $v ) {
                $items[] = '<li data-value="' . esc_attr( $v->slug ) . '">' . $v->name . '</li>'; 
            }
        }

        // @since   1.0.6
        if($atts['retrieve_method']=='csv') {
            $file = get_attached_file($atts['retrieve_method_csv']);
            if($file){
                $row = 1;
                if ( ( $handle = fopen( $file, "r" ) ) !== false ) {
                    while ( ( $data = fgetcsv( $handle, 1000, "," ) ) !== false ) {
                        $num = count( $data );
                        $row++;
                        for ( $c=0; $c < $num; $c++ ) {
                            $pieces = explode( ";", $data[$c] );
                            $items[] = '<li data-value="' . esc_attr( $pieces[0] ) . '">' . $pieces[1] . '</li>'; 
                        }
                    }
                    fclose($handle);
                }
            }
        }

        if( $placeholder!='' ) {
            $atts['placeholder'] = $placeholder;
        }
        if( empty( $atts['placeholder'] ) ) {
            $atts['placeholder'] = $atts['dropdown_items'][0]['label'];
            $atts['value'] = $atts['dropdown_items'][0]['value'];
            $atts['dropdown_items'][0]['checked'] = true;
            $items[0] = '<li data-value="' . esc_attr( $atts['value'] ) . '" class="selected">' . $atts['placeholder'] . '</li>'; 
        }
        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }
        $result .= '<input class="super-shortcode-field" type="hidden"';
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';
        $result .= '<ul class="super-dropdown-ui' . $multiple . '">';
        $result .= '<li data-value="" class="super-placeholder">' . $atts['placeholder'] . '</li>';
        foreach( $items as $v ) {
            $result .= $v;
        }
        $result .= '</ul>';
        $result .= '<span class="super-dropdown-arrow"></span>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;    
    }
    public static function dropdown_items( $tag, $atts ) {
        return '<li data-value="' . esc_attr( $atts['value'] ) . '">' . $atts['label'] . '</li>'; 
    }
    public static function checkbox( $tag, $atts ) {
        $classes = ' display-' . $atts['display'];
        $result = self::opening_tag( $tag, $atts, $classes );
        $result .= self::opening_wrapper( $atts );
        foreach( $atts['checkbox_items'] as $k => $v ) {
            $result .= '<label><input ' . ( (($v['checked']==='false') || ($v['checked']===false)) ? '' : 'checked="checked"' ) . ' type="checkbox" value="' . esc_attr( $v['value'] ) . '" />' . $v['label'] . '</label>';
        }

        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }
        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        $result .= '<input class="super-shortcode-field" type="hidden"';
        $result .= ' name="' . esc_attr( $atts['name'] ) . '" value="' . $atts['value'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function checkbox_items( $tag, $atts ) {
        return '<label><input ' . ( (($atts['checked']==='false') || ($atts['checked']===false)) ? '' : 'checked="checked"' ) . ' type="checkbox" value="' . esc_attr( $atts['value'] ) . '" />' . $atts['label'] . '</label>';
    }
    public static function radio( $tag, $atts ) {
        $classes = ' display-' . $atts['display'];
        $result = self::opening_tag( $tag, $atts, $classes );
        $result .= self::opening_wrapper( $atts );
        foreach( $atts['radio_items'] as $k => $v ) {
            $result .= '<label><input ' . ( (($v['checked']==='false') || ($v['checked']===false)) ? '' : 'checked="checked"' ) . ' type="radio" value="' . esc_attr( $v['value'] ) . '" />' . $v['label'] . '</label>';
        }
        
        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }
        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        $result .= '<input class="super-shortcode-field" type="hidden"';
        $result .= ' name="' . esc_attr( $atts['name'] ) . '" value="' . $atts['value'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function radio_items( $tag, $atts ) {
        return '<label><input ' . ( (($atts['checked']==='false') || ($atts['checked']===false)) ? '' : 'checked="checked"' ) . ' type="radio" value="' . esc_attr( $atts['value'] ) . '" />' . $atts['label'] . '</label>';
    }
    public static function file( $tag, $atts ) {
        $dir = SUPER_PLUGIN_FILE . 'assets/js/frontend/jquery-file-upload/';
        wp_enqueue_script( 'super-upload-ui-widget', $dir . 'vendor/jquery.ui.widget.js', array( 'jquery' ), SUPER_VERSION, false );
        wp_enqueue_script( 'super-upload-iframe-transport', $dir . 'jquery.iframe-transport.js', array( 'jquery' ), SUPER_VERSION, false );
        wp_enqueue_script( 'super-upload-fileupload', $dir . 'jquery.fileupload.js', array( 'jquery' ), SUPER_VERSION, false );
        wp_enqueue_script( 'super-upload-fileupload-process', $dir . 'jquery.fileupload-process.js', array( 'jquery' ), SUPER_VERSION, false );
        wp_enqueue_script( 'super-upload-fileupload-validate', $dir . 'jquery.fileupload-validate.js', array( 'jquery' ), SUPER_VERSION, false );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts );
        $result .= '<div class="super-fileupload-button"';
        $style = '';
        if( empty( $atts['extensions'] ) ) {
            $atts['extensions'] = 'gif|jpe?g|png';
        }
        if( !isset( $atts['width'] ) ) $atts['width'] = 0;
        if( $atts['width']!=0 ) {
            $style .= 'width:' . $atts['width'] . 'px;';
        }
        if( !empty( $styles ) ) {
            $style .= $styles;
        }
        if( !empty( $style ) ) {
            $result .= ' style="'.$style.'"';
        }
        $result .= '><i class="fa fa-plus"></i><span class="super-fileupload-button-text">' . $atts['placeholder'] . '</span></div>';
        $atts['placeholder'] = '';
        $result .= '<input class="super-shortcode-field super-fileupload" type="file" name="files[]" data-file-size="' . $atts['filesize'] . '" data-accept-file-types="/(\.|\/)(' . $atts['extensions'] . ')$/i" data-url="' . SUPER_PLUGIN_FILE . 'uploads/php/"';
        if( !isset( $atts['maxlength'] ) ) $atts['maxlength'] = 0;
        if( !isset( $atts['minlength'] ) ) $atts['minlength'] = 0;
        if( ($atts['minlength']>1) || ($atts['maxlength']>1) ) $result .= ' multiple';
        $result .= ' />';
        $result .= '<input class="super-selected-files" type="hidden"';
        $result .= ' value="" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';
        $result .= '<div class="super-progress-bar"></div>';
        $result .= '<div class="super-fileupload-files"></div>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function date( $tag, $atts ) {
        wp_enqueue_script( 'jquery-ui-datepicker', false, array( 'jquery' ), SUPER_VERSION );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts );
        $result .= '<input class="super-shortcode-field super-datepicker" type="text" autocomplete="off" ';
        $format = $atts['format'];
        if( $format=='custom' ) $format = $atts['custom_format'];

        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }

        // @since 1.1.8 - added option to select an other datepicker to achieve date range with 2 datepickers (useful for booking forms)
        if( !isset( $atts['connected_min'] ) ) $atts['connected_min'] = '';
        if( !isset( $atts['connected_max'] ) ) $atts['connected_max'] = '';

        if( !isset( $atts['range'] ) ) $atts['range'] = '-100:+5';
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '" data-format="' . $format . '" data-connected_min="' . $atts['connected_min'] . '" data-connected_max="' . $atts['connected_max'] . '" data-range="' . $atts['range'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function time( $tag, $atts ) {
        wp_enqueue_script( 'jquery-timepicker', SUPER_PLUGIN_FILE . 'assets/js/frontend/timepicker.min.js' );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts );

        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }
        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        $result .= '<input class="super-shortcode-field super-timepicker" type="text" autocomplete="off" ';
        if( !isset( $atts['range'] ) ) $atts['range'] = '';
        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '" data-format="' . $atts['format'] . '" data-step="' . $atts['step'] . '" data-range="' . $atts['range'] . '" data-duration="' . $atts['duration'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }    
    public static function rating( $tag, $atts ) {
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts );
        $result .= '<div class="super-rating">';
        $result .= '<i class="fa fa-star super-rating-star"></i>';
        $result .= '<i class="fa fa-star super-rating-star"></i>';
        $result .= '<i class="fa fa-star super-rating-star"></i>';
        $result .= '<i class="fa fa-star super-rating-star"></i>';
        $result .= '<i class="fa fa-star super-rating-star"></i>';
        
        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }
        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        $result .= '<input class="super-shortcode-field super-star-rating" type="hidden"';
        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';
        $result .= '</div>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function skype( $tag, $atts ) {
        wp_enqueue_script( 'super-skype', 'http://www.skypeassets.com/i/scom/js/skype-uri.js' );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts );
        if( !isset( $atts['username'] ) ) $atts['username'] = '';
        $result .= '<div id="SkypeButton_Call_' . $atts['username'] . '" class="super-skype-button" data-username="' . $atts['username'] . '" data-method="' . $atts['method'] . '" data-color="' . $atts['color'] . '" data-size="' . $atts['size'] . '"></div>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function countries( $tag, $atts ) {
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts );

        $multiple = '';
        if( !isset( $atts['maxlength'] ) ) $atts['maxlength'] = 0;
        if( !isset( $atts['minlength'] ) ) $atts['minlength'] = 0;
        if( ($atts['minlength']>1) || ($atts['maxlength']>1) ) $multiple = ' multiple';

        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }
        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        $result .= '<input class="super-shortcode-field" type="hidden"';
        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';
        $result .= '<ul class="super-dropdown-ui' . $multiple . '">';
        if( !empty( $atts['placeholder'] ) ) {
            $result .= '<li data-value="" class="super-placeholder">' . $atts['placeholder'] . '</li>';
        }else{
            $result .= '<li data-value="" class="super-placeholder"></li>';
        }
        
        /**
         *  On some servers file_get_contents might return 403 Forbidden error to prevent scraping
         *  Therefore we will use curl instead and set a fake user agent
         *
         *  @since   1.1.4
        */
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, SUPER_PLUGIN_FILE . 'countries.txt' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13' );
        $countries = curl_exec( $ch );
        curl_close( $ch );
        $countries = explode( "\n", $countries );
        foreach( $countries as $k => $v ){
            $v = trim($v);
            $result .= '<li data-value="' . esc_attr( $v ) . '">' . $v . '</li>'; 
        }
        $result .= '</ul>';
        $result .= '<span class="super-dropdown-arrow"></span>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function password( $tag, $atts ) {
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts );

        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }
        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        $result .= '<input class="super-shortcode-field" type="password"';
        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function hidden( $tag, $atts ) {
        $classes = ' hidden';
        $result = self::opening_tag( $tag, $atts, $classes );

        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }
        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        if( !isset( $atts['exclude'] ) ) $atts['exclude'] = 0;
        $result .= '<input class="super-shortcode-field" type="hidden" value="' . $atts['value'] . '" name="' . $atts['name'] . '" data-email="' . $atts['email'] . '" data-exclude="' . $atts['exclude'] . '" />';
        $result .= '</div>';
        return $result;
    }
    public static function image( $tag, $atts ) {
        $result = self::opening_tag( $tag, $atts );
        $style = '';
        if( $atts['height']!=0 ) $style .= 'max-height:' . $atts['height'] . 'px;';
        if( $atts['width']!=0 ) $style .= 'max-width:' . $atts['width'] . 'px;';
        if( !isset( $atts['image'] ) ) $atts['image'] = 0;
        $image = wp_prepare_attachment_for_js( $atts['image'] );
        $url = '';
        if( !isset( $atts['link'] ) ) $atts['link'] = '';
        if( $atts['link']!='' ) {
            if( $atts['link']=='custom' ) {
                $url = $atts['custom_link'];
            }else{
                $url = get_permalink( $atts['link'] );
            }
            $url = ' href="' . $url . '"';
        }
        $result .= '<div class="super-image align-' . $atts['alignment'] . '" itemscope="itemscope" itemtype="https://schema.org/ImageObject">';
        $result .= '<div class="super-image-inner">';
        if( !isset( $atts['target'] ) ) $atts['target'] = '';
        $result .= '<a target="' . $atts['target'] . '"' . $url . '>';
        $result .= '<img src="' . $image['url'] . '" alt="' . $image['alt'] . '" title="' . $image['title'] . '" itemprop="contentURL"';
        if( !empty( $style ) ) $result .= ' style="' . $style . '"';
        $result .= '>';
        $result .= '</a>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function html( $tag, $atts ) {
        $result = self::opening_tag( $tag, $atts );
        if( !isset( $atts['title'] ) ) $atts['title'] = '';
        if( $atts['title']!='' ) {
            $result .= '<div class="super-html-title">' . $atts['title'] . '</div>';
        }
        if( !isset( $atts['subtitle'] ) ) $atts['subtitle'] = '';
        if( $atts['subtitle']!='' ) {    
            $result .= '<div class="super-html-subtitle"">' . $atts['subtitle'] . '</div>';
        }
        if( $atts['html']!='' ) {    
            $result .= '<div class="super-html-content"">' . do_shortcode( $atts['html'] ) . '</div>';
        }
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function recaptcha( $tag, $atts ) {
        wp_enqueue_script('super-recaptcha', 'https://www.google.com/recaptcha/api.js?onload=SUPER.reCaptcha&render=explicit');
        $settings = get_option('super_settings');
        $result = self::opening_tag( $tag, $atts );
        if( !isset( $atts['error'] ) ) $atts['error'] = '';
        if( !isset( $atts['align'] ) ) $atts['align'] = '';
        if( !empty( $atts['align'] ) ) $atts['align'] = ' align-' . $atts['align'];
        $result .= '<div class="super-recaptcha' . $atts['align'] . '" data-key="' . $settings['form_recaptcha'] . '" data-message="' . $atts['error'] . '"></div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function divider( $tag, $atts ) {
        $classes = ' align-' . $atts['align'] . ' border-' . $atts['border'] . ' style-' . $atts['border_style'] . ' back-' . $atts['back'];
        $styles = 'padding-top:' . $atts['padding_top'] . 'px;padding-bottom:' . $atts['padding_bottom'] . 'px;';
        $result = self::opening_tag( $tag, $atts, $classes, $styles );
        $styles = '';
        $i_styles = '';
        if( $atts['width']!='100' ) {
            $styles .= 'display:inline-block;';
        }
        if( $atts['width']=='custom' ) {
            $styles .= 'width:' . $atts['custom_width'] . ';';
        }else{
            $styles .= 'width:' . $atts['width'].'%;';
        }
        if( $atts['color']!='' ) {
            $styles .= 'border-color:' . $atts['color'] . ';';
            $i_styles .= ' style="color:' . $atts['color'] . ';"';
        }
        if( $atts['thickness']!='' ) {
            $styles .= 'border-top-width:' . $atts['thickness'] . 'px;border-bottom-width:' . $atts['thickness'] . 'px;';
        }
        $styles .= 'height:' . $atts['height'] . 'px;';
        $result .= '<div class="super-divider-inner" style="' . $styles . '">';
        if( $atts['back']==1 ) {
            $result .= '<span class="super-back-to-top"' . $i_styles . '><i class="fa fa-chevron-up"></i></span>';
        }
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function spacer( $tag, $atts ) {
        $styles = '';
        if( $atts['height']!='' ) {
            $styles = 'height:' . $atts['height'] . 'px;';
        }
        $result = self::opening_tag( $tag, $atts, '', $styles );
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }

    /** 
     *  Add button to allow conditional logic show/hide button
     *
     * @param  string  $tag
     * @param  array   $atts
     *
     *  @since      1.1.6
    */
    public static function button( $tag, $atts, $inner, $shortcodes, $settings ) {
        $name = $settings['form_button'];
        $radius = $settings['form_button_radius'];
        $type = $settings['form_button_type'];
        $size = $settings['form_button_size'];
        $align = $settings['form_button_align'];
        $width = $settings['form_button_width'];
        $icon_option = $settings['form_button_icon_option'];
        $icon_visibility = $settings['form_button_icon_visibility'];
        $icon_animation = $settings['form_button_icon_animation'];
        $icon = $settings['form_button_icon'];
        $color = $settings['theme_button_color'];
        $color_hover = $settings['theme_button_color_hover'];
        $font = $settings['theme_button_font'];
        $font_hover = $settings['theme_button_font_hover'];

        if( isset( $atts['name'] ) ) $name = $atts['name'];
        
        if( isset( $atts['custom_advanced'] ) ) {
            if( $atts['custom_advanced']=='custom' ) {
                if( isset( $atts['radius'] ) ) $radius = $atts['radius'];
                if( isset( $atts['type'] ) ) $type = $atts['type'];
                if( isset( $atts['size'] ) ) $size = $atts['size'];
                if( isset( $atts['align'] ) ) $align = $atts['align'];
                if( isset( $atts['width'] ) ) $width = $atts['width'];
            }
        }
        if( isset( $atts['custom_icon'] ) ) {
            if( $atts['custom_icon']=='custom' ) {
                if( isset( $atts['icon_option'] ) ) $icon_option = $atts['icon_option'];
                if( isset( $atts['icon_visibility'] ) ) $icon_visibility = $atts['icon_visibility'];
                if( isset( $atts['icon_animation'] ) ) $icon_animation = $atts['icon_animation'];
                if( isset( $atts['icon'] ) ) $icon = $atts['icon'];
            }
        }
        if( isset( $atts['custom_colors'] ) ) {
            if( $atts['custom_colors']=='custom' ) {
                if( isset( $atts['color'] ) ) $color = $atts['color'];
                if( isset( $atts['color_hover'] ) ) $color_hover = $atts['color_hover'];
                if( isset( $atts['font'] ) ) $font = $atts['font'];
                if( isset( $atts['font_hover'] ) ) $font_hover = $atts['font_hover'];
            }
        }

        $class = 'super-extra-shortcode super-shortcode super-field super-form-button super-clear-none ';
        $class .= 'super-button super-radius-' . $radius . ' super-type-' . $type . ' super-button-' . $size . ' super-button-align-' . $align . ' super-button-width-' . $width;
        if( $icon_option!='none' ) {
            $class .= ' super-button-icon-option-' . $icon_option . ' super-button-icon-animation-' . $icon_animation . ' super-button-icon-visibility-' . $icon_visibility;
        }
        $attributes = '';
        if( $color!='' ) {
            $light = SUPER_Common::adjust_brightness( $color, 20 );
            $dark = SUPER_Common::adjust_brightness( $color, -30 );
            $attributes .= ' data-color="' . $color . '"';
            $attributes .= ' data-light="' . $light . '"';
            $attributes .= ' data-dark="' . $dark . '"';
        }
        if( $color_hover!='' ) {
            $hover_light = SUPER_Common::adjust_brightness( $color_hover, 20 );
            $hover_dark = SUPER_Common::adjust_brightness( $color_hover, -30 );
            $attributes .= ' data-hover-color="' . $color_hover . '"';
            $attributes .= ' data-hover-light="' . $hover_light . '"';
            $attributes .= ' data-hover-dark="' . $hover_dark . '"';
        }
        if( $font!='' ) $attributes .= ' data-font="' . $font .'"';
        if( $font_hover!='' ) $attributes .= ' data-font-hover="' . $font_hover . '"';
        $result = '';
        $result .= '<div' . $attributes . ' data-radius="' . $radius . '" data-type="' . $type . '" class="' . $class . '">';
            $url = '';
            if( !isset( $atts['link'] ) ) $atts['link'] = '';
            if( $atts['link']!='' ) {
                if( $atts['link']=='custom' ) {
                    $url = $atts['custom_link'];
                }else{
                    $url = get_permalink( $atts['link'] );
                }
            }
            if( !isset( $atts['target'] ) ) $atts['target'] = '';
            if( !empty( $atts['target'] ) ) $atts['target'] = 'target="' . $atts['target'] . '" ';
            $result .= '<a ' . $atts['target'] . 'href="' . $url . '">';
                $result .= '<div class="super-button-name">';
                    if( ( $icon!='' ) && ( $icon_option!='none' ) ) {
                        $result .= '<i class="fa fa-' . $icon . '"></i>';
                    }
                    $result .= $name;
                $result .= '</div>';
                $result .= '<span class="super-after"></span>';
            $result .= '</a>';
        $result .= '</div>';
        return $result;
    }


    /** 
     *  Output the shortcode element on backend create form page under Tabs: Layout / Form Elements etc.
     *
     * @param  string  $tag
     * @param  string  $group
     * @param  array   $data
     * @param  array   $shortcodes
     *
     *  @since      1.0.0
    */
    public static function output_element_html( $tag, $group, $data, $inner, $shortcodes=null, $settings=null ) {
        if( $shortcodes==null ) {
            $shortcodes = self::shortcodes();
        }
        if( !isset( $shortcodes[$group]['shortcodes'][$tag] ) ) {
            return '';
        }
        $callback = $shortcodes[$group]['shortcodes'][$tag]['callback'];
        $callback = explode( '::', $callback );
        $class = $callback[0];
        $function = $callback[1];
        $data = json_decode(json_encode($data), true);
        $inner = json_decode(json_encode($inner), true);
        return call_user_func( array( $class, $function ), $tag, $data, $inner, $shortcodes, $settings );
    }

    
    /** 
     *  Output the shortcode element on backend create form page under Tabs: Layout / Form Elements etc.
     *
     * @param  string $shortcode
     * @param  array $value
     *
     *  @since      1.0.0
    */
    public static function output_element( $shortcode=null, $group='form_elements', $value=array() ) {
        
        
        $return = '';
        
        // Form Elements
        $return .= '<div class="super-element draggable-element super-shortcode-'.$shortcode.'" data-elementtitle="'.$value['name'].'" data-shortcode="'.$shortcode.'" data-group="'.$group.'" data-droppingallowed="0">';
            if( isset( $value['html'] ) ) {
                $return .= $value['html'];
            }else{
                $return .= '<i class="fa fa-'.$value['icon'].'"></i>'.$value['name'];
                $return .= '<div class="content" style="display:none;">';
                if( isset( $value['content'] ) ) $return .= $value['content'];
                $return .= '</div>';
            }
            if( isset( $value['predefined'] ) ) {
                $return .= '<textarea class="predefined" style="display:none;">' . json_encode( $value['predefined'] ) . '</textarea>';
            }
        $return .= '</div>';
            
        return apply_filters( 'super_backend_output_element_'.$shortcode.'_filter', $return, array( 'shortcode'=>$shortcode, 'value'=>$value ) );
        
    }
    

    /** 
     *  The form shortcode that will generate all fields on the frontend
     *
     * @param  array $atts
     * @param  string $content
     *
     *  @since      1.0.0
    */
    public static function super_form_func( $atts ) {
        
        extract( shortcode_atts( array(
            'id' => '',
        ), $atts ) );

        // Sanitize the ID
        $id = absint($id);

        // Check if the post exists
        if ( FALSE === get_post_status( $id ) ) {
            // The post does not exist
            $result = '<strong>'.__('Error', 'super').':</strong> '.sprintf(__('Super Forms could not find a form with ID: %d', 'super'), $id);
            return $result;
        }

        /** 
         *  Make sure that we have all settings even if this form hasn't saved it yet when new settings where added by a add-on
         *
         *  @since      1.0.6
        */
        require( SUPER_PLUGIN_DIR . '/includes/class-settings.php' );
        $fields = SUPER_Settings::fields( null, 1 );
        $array = array();
        foreach( $fields as $k => $v ) {
            if( !isset( $v['fields'] ) ) continue;
            foreach( $v['fields'] as $fk => $fv ) {
                if( ( isset( $fv['type'] ) ) && ( $fv['type']=='multicolor' ) ) {
                    foreach( $fv['colors'] as $ck => $cv ) {
                        if( !isset( $cv['default'] ) ) $cv['default'] = '';
                        $array[$ck] = $cv['default'];
                    }
                }else{
                    if( !isset( $fv['default'] ) ) $fv['default'] = '';
                    $array[$fk] = $fv['default'];
                }
            }
        }
        $settings = get_post_meta($id, '_super_form_settings', true );
        $settings = array_merge( $array, $settings );
        SUPER_Forms()->enqueue_element_styles();
        SUPER_Forms()->enqueue_element_scripts($settings);

        // If post exists get the settings
        $theme_styles = '';
        if( ( isset( $settings['theme_max_width'] ) ) && ( $settings['theme_max_width']!=0 ) ) {
            $theme_styles = 'style="max-width:' . $settings['theme_max_width'] . 'px;" ';
        }

        // Try to load the selected theme style
        $theme_style = 'style-default ';
        $style_content  = '';
        if( ( isset( $settings['theme_style'] ) ) && ( $settings['theme_style']!='' ) ) {
            $theme_style = $theme_style . $settings['theme_style'];
            $style_content .= require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/' . str_replace( 'super-', '', $settings['theme_style'] ) . '.php' );
        }

        // Always load the default styles (these can be overwritten by the above loaded style file
        $style_content .= require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/style-default.php' );
        
        $result = '';
        $result .= '<div ' . $theme_styles . 'class="super-form ' . ( $settings['form_preload'] == 0 ? 'active ' : '' ) . 'super-form-' . $id . ' ' . $theme_style . '">'; 
        
        // Check if plugin is activated
        $sac = get_option( 'super_la', 0 );
        if( $sac!=1 ) {
            $result .= '<div class="super-msg error"><h1>Please note:</h1>';
            $result .= __( 'You haven\'t activated your Super Forms Plugin yet', 'super-forms' ).'<br />';
            $result .= __( 'Please click <a target="_blank" href="' . admin_url() . 'admin.php?page=super_settings#0">here</a> and enter you Purchase Code under the Activation TAB.', 'super-forms' );
            $result .= '<span class="close"></span></div>';
            $result .= '</div>';
            return $result;
        }

        $result .= '<div class="super-shortcode super-field hidden">';
        $result .= '<input class="super-shortcode-field" type="hidden" value="' . $id . '" name="hidden_form_id" />';
        $result .= '</div>';
        
        // Loop through all form elements
        $GLOBALS['super_found_button'] = false;
        $elements = json_decode( get_post_meta( $id, '_super_elements', true ) );
        if( !empty( $elements ) ) {
            $shortcodes = self::shortcodes();
            // Before doing the actuall loop we need to know how many columns this form contains
            // This way we can make sure to correctly close the column system
            $GLOBALS['super_column_found'] = 0;
            foreach( $elements as $k => $v ) {
                if( $v->tag=='column' ) $GLOBALS['super_column_found']++;
            }
            foreach( $elements as $k => $v ) {
                if( $v->tag=='button' ) $GLOBALS['super_found_button'] = true;
                $result .= self::output_element_html( $v->tag, $v->group, $v->data, $v->inner, $shortcodes, $settings );
            }
        }
        if( $GLOBALS['super_found_button']==false ) {
            $result .= self::button( 'button', array(), '', '', $settings );
        }
        $result .= '</div>';
        $settings = get_option('super_settings');
        $result .= '<style type="text/css">' . apply_filters( 'super_form_styles_filter', $style_content, array( 'id'=>$id, 'settings'=>$settings ) ) . $settings['theme_custom_css'] . '</style>';        
        return do_shortcode( $result );

    }    
    
    /** 
     *  Common fields for each shortcode (backend dialog)
     *
     *  @since      1.0.0
    */
    public static function name( $attributes=null, $default='' ) {
        $array = array(
            'name'=>__( 'Unique field name', 'super-forms' ), 
            'desc'=>__( 'Must be an unique name (required)', 'super-forms' ),
            'default'=> ( !isset( $attributes['name'] ) ? $default : $attributes['name'] ),
            'required'=>true,
        );
        return $array;
    }
    public static function email( $attributes=null, $default='' ) {
        $array = array(
            'name'=>__( 'Email Label', 'super-forms' ), 
            'desc'=>__( 'Indicates the field in the email template. (required)', 'super-forms' ),
            'default'=> ( !isset( $attributes['email'] ) ? $default : $attributes['email'] ),
            'required'=>true,
        );
        return $array;
    }
    public static function label( $attributes=null, $default='' ) {
        $array = array(
            'name'=>__( 'Field Label', 'super-forms' ), 
            'desc'=>__( 'Will be visible in front of your field.', 'super-forms' ).' ('.__( 'leave blank to remove', 'super-forms' ).')',
            'default'=> ( !isset( $attributes['label'] ) ? $default : $attributes['label'] ),
        );
        return $array;
    }    
    public static function description( $attributes=null, $default='') {
        $array = array(
            'name'=>__( 'Field description', 'super-forms' ), 
            'desc'=>__( 'Will be visible in front of your field.', 'super-forms' ).' ('.__( 'leave blank to remove', 'super-forms' ).')',
            'default'=> ( !isset( $attributes['description'] ) ? $default : $attributes['description'] ),
        );
        return $array;
    }
    public static function icon( $attributes=null, $default='user' ) {
        $icon = array(
            'default'=> ( !isset( $attributes['icon'] ) ? $default : $attributes['icon'] ),
            'name'=>__( 'Select an Icon', 'super-forms' ), 
            'type'=>'icon',
            'desc'=>__( 'Leave blank if you prefer to not use an icon.', 'super-forms' )
        );
        return $icon;
    }
    public static function placeholder( $attributes=null, $default=null ) {
        $array = array(
            'default'=> ( !isset( $attributes['placeholder'] ) ? $default : $attributes['placeholder'] ),
            'name'=>__( 'Placeholder', 'super-forms' ), 
            'desc'=>__( 'Indicate what the user needs to enter or select. (leave blank to remove)', 'super-forms' )
        );
        return $array;
    }
    public static function width( $attributes=null, $default=0, $min=0, $max=600, $steps=10, $name=null, $desc=null ) {
        if( empty( $name ) ) $name = __( 'Field width in pixels', 'super-forms' );
        if( empty( $desc ) ) $desc = __( 'Set to 0 to use default CSS width.', 'super-forms' );
        $array = array(
            'type' => 'slider', 
            'default'=> ( !isset( $attributes['width'] ) ? $default : $attributes['width'] ),
            'min' => $min, 
            'max' => $max, 
            'steps' => $steps, 
            'name' => $name, 
            'desc' => $desc
        );
        return $array;
    }
    public static function slider( $attributes=null, $default=0, $min=0, $max=600, $steps=10, $name=null, $desc=null, $key=null ) {
        if( empty( $name ) ) $name = __( 'Field width in pixels', 'super-forms' );
        if( empty( $desc ) ) $desc = __( 'Set to 0 to use default CSS width.', 'super-forms' );
        $array = array(
            'type' => 'slider', 
            'default'=> ( !isset( $attributes[$key] ) ? $default : $attributes[$key] ),
            'min' => $min, 
            'max' => $max, 
            'steps' => $steps, 
            'name' => $name, 
            'desc' => $desc
        );
        return $array;
    }
    public static function minlength( $attributes=null, $default=0, $min=0, $max=100, $steps=1, $name=null, $desc=null) {
        if( empty($name ) ) $name = __( 'Min characters/selections allowed', 'super-forms' );
        if( empty($desc ) ) $desc = __( 'Set to 0 to remove limitations.', 'super-forms' );
        $array = array(
            'type' => 'slider', 
            'default'=> ( !isset( $attributes['minlength'] ) ? $default : $attributes['minlength'] ),
            'min' => $min, 
            'max' => $max, 
            'steps' => $steps, 
            'name' => $name, 
            'desc' => $desc
        );
        return $array;
    }
    public static function maxlength( $attributes=null, $default=0, $min=0, $max=100, $steps=1, $name=null, $desc=null ) {
        if( empty( $name ) ) $name = __( 'Max characters/selections allowed', 'super-forms' );
        if( empty( $desc ) ) $desc = __( 'Set to 0 to remove limitations.', 'super-forms' );
        $array = array(
            'type' => 'slider', 
            'default'=> ( !isset( $attributes['maxlength'] ) ? $default : $attributes['maxlength'] ),
            'min' => $min, 
            'max' => $max, 
            'steps' => $steps, 
            'name' => $name, 
            'desc' => $desc
        );
        return $array;
    }
    
}

endif;