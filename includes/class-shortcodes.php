<?php
/**
 * Super Forms Shortcodes Class.
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
                        if( (isset($vv['default'])) && ($vv['default']!=='') && ($vv['default']!==0) ) {
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
                    $result .= '<div class="resize super-tooltip" data-content="Change Column Size">';
                        $result .= '<span class="smaller"><i class="fa fa-angle-left"></i></span>';
                        $result .= '<span class="current">' . $data['size'] . '</span><span class="bigger"><i class="fa fa-angle-right"></i></span>';
                    $result .= '</div>';
                }else{
                    $result .= '<div class="super-title">' . $name . '</div>';
                }
                $result .= '<div class="super-element-actions">';
                    $result .= '<span class="edit super-tooltip" title="Edit element"><i class="fa fa-pencil"></i></span>';
                    $result .= '<span class="duplicate super-tooltip" title="Duplicate element"><i class="fa fa-files-o"></i></span>';
                    $result .= '<span class="move super-tooltip" title="Reposition element"><i class="fa fa-arrows"></i></span>';
                    $result .= '<span class="minimize super-tooltip" title="Minimize"><i class="fa fa-minus-square-o"></i></span>';
                    $result .= '<span class="delete super-tooltip" title="Delete"><i class="fa fa-times"></i></span>';
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
        if($tag!='image'){
            if( !isset( $atts['width'] ) ) $atts['width'] = 0;
            if( $atts['width']!=0 ) $style .= 'width:' . $atts['width'] . 'px;';
        }
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

        if( !empty( $atts['tooltip'] ) ) $result .= ' super-tooltip';
        if( !isset( $atts['error_position'] ) ) $atts['error_position'] = '';
        $result .= ' ' . $atts['error_position'];
        //if(($tag=='super_checkbox') || ($tag=='super_radio') || ($tag=='super_shipping')) $result .= ' display-'.$display;
        if( !isset( $atts['grouped'] ) ) $atts['grouped'] = 0;
        if($atts['grouped']==0) $result .= ' ungrouped ';
        if($atts['grouped']==1) $result .= ' grouped ';
        if($atts['grouped']==2) $result .= ' grouped grouped-end ';

        // @since 1.9 - custom wrapper class
        if($tag=='spacer'){
            if( !isset( $atts['class'] ) ) $atts['class'] = '';     
            $result .= ' ' . $class . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"';
        }else{
            if( !isset( $atts['wrapper_class'] ) ) $atts['wrapper_class'] = '';     
            $result .= ' ' . $class . ($atts['wrapper_class']!='' ? ' ' . $atts['wrapper_class'] : '') . '"';
        }

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
        return '<div class="super-label' . $class . '">' . stripslashes($label) . '</div>';
    }
    public static function field_description( $description ) {        
        return '<div class="super-description">' . stripslashes($description) . '</div>';
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

        $result = '<div' . $style . ' class="super-field-wrapper' . ($atts['icon']!='' ? ' super-icon-' . $atts['icon_position'] . ' super-icon-' . $atts['icon_align'] : '') . '">';
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

        // @since 2.6.0 - IBAN validation
        if($atts['validation']=='iban') {
            wp_enqueue_script( 'super-iban-check', SUPER_PLUGIN_FILE . 'assets/js/frontend/iban-check.min.js', array(), SUPER_VERSION );
        }

        $result = ' data-message="' . $atts['error'] . '" data-validation="'.$atts['validation'].'" data-may-be-empty="'.$atts['may_be_empty'].'" data-conditional-validation="'.$atts['conditional_validation'].'" data-conditional-validation-value="'.$atts['conditional_validation_value'].'" data-email="'.$atts['email'].'" data-exclude="'.$atts['exclude'].'"';
        
        // @since 2.0.0 - default value data attribute needed for Clear button
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        if($atts['value']!='') $result .= ' data-default-value="' . $atts['value'] . '"';

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

            // @since 1.2.9
            if( !isset( $atts['contact_entry_value'] ) ) $atts['contact_entry_value'] = 'value';
            $result .= ' data-contact-entry-value="' . $atts['contact_entry_value'] . '"';

            if( ($tag=='dropdown') || ($tag=='checkbox') ) {
                // @since 2.0.0
                if( $atts['maxlength']>0 ) {
                    $result .= ' data-maxlength="' . $atts['maxlength'] . '"';
                }
                if( $atts['minlength']>0 ) {
                    $result .= ' data-minlength="' . $atts['minlength'] . '"';
                }
            }

        }else{
            if($tag=='date'){
                if( $atts['maxlength']!='' ) {
                    $result .= ' data-maxlength="' . $atts['maxlength'] . '"';
                }
                if( $atts['minlength']!='' ) {
                    $result .= ' data-minlength="' . $atts['minlength'] . '"';
                }
            }else{
                if( $tag=='text' ) {
                    // @since   1.3   - predefined input mask e.g: (___) ___-____
                    if( !isset( $atts['mask'] ) ) $atts['mask'] = '';
                    if( $atts['mask']!='' ) {
                        wp_enqueue_script( 'super-masked-input', SUPER_PLUGIN_FILE . 'assets/js/frontend/masked-input.min.js', array(), SUPER_VERSION );
                        $result .= ' data-mask="' . $atts['mask'] . '"';
                    }
                }
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
            
            // @since 2.3.0 - speed improvement for conditional logics
            // append the field names ad attribute that the conditions being applied to, so we can filter on it on field change with javascript
            $fields = array();
            foreach( $atts['conditional_items'] as $k => $v ) {
                if( !isset( $v['logic'] ) ) $v['logic'] = '';
                if( !isset( $v['logic_and'] ) ) $v['logic_and'] = '';
                if( $v['logic']!='' ) $fields[$v['field']] = $v['field'];
                if( $v['logic_and']!='' ) $fields[$v['field_and']] = $v['field_and'];
            }
            $fields = implode('][', $fields);

            // @since 1.7 - use json instead of HTML for speed improvements
            return '<textarea class="super-conditional-logic" data-fields="[' . $fields . ']">' . json_encode($atts['conditional_items']) . '</textarea>';

            /*
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
            */

        }
    }
    
    // @since 1.2.7    - variable conditions
    public static function loop_variable_conditions( $atts ) {
        if( !isset( $atts['conditional_variable_action'] ) ) $atts['conditional_variable_action'] = 'disabled';
        if( !isset( $atts['conditional_items'] ) ) $atts['conditional_items'] = '';
        if( ( $atts['conditional_items']!=null ) && ( $atts['conditional_variable_action']!='disabled' ) ) {
            
            // @since 2.3.0 - speed improvement for variable field
            // append the field names ad attribute that the conditions being applied to, so we can filter on it on field change with javascript
            $fields = array();
            $tags = array();
            foreach( $atts['conditional_items'] as $k => $v ) {
                if( !isset( $v['logic'] ) ) $v['logic'] = '';
                if( !isset( $v['logic_and'] ) ) $v['logic_and'] = '';                
                if( $v['logic']!='' ) $fields[$v['field']] = $v['field'];
                if( $v['logic_and']!='' ) $fields[$v['field_and']] = $v['field_and'];

                // @since 2.3.0 - also check if variable field contains tags and if so, update the correct values
                if( $v['new_value']!='' ) {
                    preg_match_all('/{\K[^}]*(?=})/m', $v['new_value'], $matches);
                    $tags = array_unique(array_merge($tags, $matches[0]), SORT_REGULAR);
                }
            }
            $fields = implode('][', $fields);
            $tags = implode('][', $tags);

            // @since 1.7 - use json instead of HTML for speed improvements
            return '<textarea class="super-variable-conditions" data-fields="[' . $fields . ']" data-tags="[' . $tags . ']">' . json_encode($atts['conditional_items']) . '</textarea>';

            /*
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
            */


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
    public static function multipart( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        
        // @since 1.2.3
        if( !isset( $atts['auto'] ) ) $atts['auto'] = 'no';
        
        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        // @since 2.0 - check for errors prevent clicking next button
        if( !isset( $atts['validate'] ) ) $atts['validate'] = '';

        // @since 2.6.0 - add active class to the first multipart element
        if( !isset($GLOBALS['super_first_multipart']) ) {
            $GLOBALS['super_first_multipart'] = true;
            $atts['class'] = 'active '.$atts['class']; 
        }

        $result  = '';
        $result .= '<div class="super-shortcode super-' . $tag . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" ' . ($atts['validate']=='true' ? ' data-validate="' . $atts['validate'] . '"' : '') . 'data-step-auto="' . $atts['auto'] .'" data-step-name="' . $atts['step_name'] .'" data-step-description="' . $atts['step_description'] . '"';
        
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
                $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings, $entry_data );
            }
        }
        unset($GLOBALS['super_grid_system']);
        $result .= '</div>';
        return $result;
    }
    public static function column( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {

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

        // @since   1.3   - background color
        $styles = '';
        if( !isset( $atts['bg_color'] ) ) $atts['bg_color'] = '';
        if( $atts['bg_color']!='' ) {
            
            // @since 1.9    - background opacity
            if( !isset( $atts['bg_opacity'] ) ) $atts['bg_opacity'] = 1;
            
            $styles .= 'background-color:' . SUPER_Common::hex2rgb( $atts['bg_color'], $atts['bg_opacity'] ) . ';';
        }

        // @since   1.9 - custom positioning
        if( !isset( $atts['position'] ) ) $atts['position'] = '';
        if( $atts['position']!='' ) {
            $styles .= 'position:' . $atts['position'] . ';';
            if( !isset( $atts['positioning'] ) ) $atts['positioning'] = '';
            if( !isset( $atts['positioning_top'] ) ) $atts['positioning_top'] = '';
            if( !isset( $atts['positioning_right'] ) ) $atts['positioning_right'] = '';
            if( !isset( $atts['positioning_bottom'] ) ) $atts['positioning_bottom'] = '';
            if( !isset( $atts['positioning_left'] ) ) $atts['positioning_left'] = '';
            if( $atts['positioning']=='top_left' ) {
                $styles .= 'top:' . $atts['positioning_top'] . ';';
                $styles .= 'left:' . $atts['positioning_left'] . ';';
                $styles .= 'right: inherit;';
                $styles .= 'bottom: inherit;';
            }elseif( $atts['positioning']=='top_right' ) {
                $styles .= 'top:' . $atts['positioning_top'] . ';';
                $styles .= 'right:' . $atts['positioning_right'] . ';';
                $styles .= 'left: inherit;';
                $styles .= 'bottom: inherit;';
            }elseif( $atts['positioning']=='bottom_left' ) {
                $styles .= 'bottom:' . $atts['positioning_bottom'] . ';';
                $styles .= 'left:' . $atts['positioning_left'] . ';';
                $styles .= 'right: inherit;';
                $styles .= 'top: inherit;';
            }elseif( $atts['positioning']=='bottom_right' ) {
                $styles .= 'bottom:' . $atts['positioning_bottom'] . ';';
                $styles .= 'right:' . $atts['positioning_right'] . ';';
                $styles .= 'left: inherit;';
                $styles .= 'top: inherit;';
            }
        }

        // @since   1.9 - background image
        if( !isset( $atts['bg_image'] ) ) $atts['bg_image'] = '';
        if( $atts['bg_image']!='' ) {
            $image = wp_get_attachment_image_src( $atts['bg_image'], 'original' );
            $image = !empty( $image[0] ) ? $image[0] : '';
            if( !empty( $image ) ) {
                $styles .= 'background-image: url(\'' . $image . '\');';
            }
        }

        if( $styles!='' ) $styles = ' style="' . $styles . '"';

        // Make sure our global super_grid_system is set
        if( !isset( $GLOBALS['super_grid_system'] ) ) {
            $GLOBALS['super_grid_system'] = array(
                'level' => 0,
                'width' => 0,
                'columns' => array()
            );
        }
        $grid = $GLOBALS['super_grid_system'];

        // Count inner columns of the current column
        $inner_total = 0;
        if( !empty( $inner ) ) {
            foreach( $inner as $k => $v ) {
                if( $v['tag']=='column' ) $inner_total++;
            }
        }
        $GLOBALS['super_grid_system']['columns'][$grid['level']]['inner_total'] = $inner_total;
        if( !isset( $GLOBALS['super_column_found'] ) ) $GLOBALS['super_column_found'] = 0;
        if( !isset( $grid['columns'][$grid['level']]['total'] ) ) $GLOBALS['super_grid_system']['columns'][$grid['level']]['total'] = $GLOBALS['super_column_found'];
        if( !isset( $grid['columns'][$grid['level']]['current'] ) ) $GLOBALS['super_grid_system']['columns'][$grid['level']]['current'] = 0;
        if( !isset( $grid['columns'][$grid['level']]['absolute_current'] ) ) $GLOBALS['super_grid_system']['columns'][$grid['level']]['absolute_current'] = 0;
        if( !isset( $grid[$grid['level']]['width'] ) ) $GLOBALS['super_grid_system'][$grid['level']]['width'] = 0;
        $grid = $GLOBALS['super_grid_system'];

        $result = '';
        $close_grid = false;
        $grid[$grid['level']]['width'] = floor($grid[$grid['level']]['width']+$sizes[$atts['size']][1]);  
        if( $grid[$grid['level']]['width']>100 ) {
            $grid[$grid['level']]['width'] = $sizes[$atts['size']][1];
            $grid['columns'][$grid['level']]['current'] = 0;
            $result .= '</div>';
        }
        $grid['columns'][$grid['level']]['current']++;
        $grid['columns'][$grid['level']]['absolute_current']++;
        $class = '';
        if($grid['columns'][$grid['level']]['current']==1){
            $class = 'first-column';
            $result .= '<div class="super-grid super-shortcode">';
        }
        if( ( $grid[$grid['level']]['width']>=95 ) || ( $grid['columns'][$grid['level']]['total']==$grid['columns'][$grid['level']]['current'] ) ) {
            $close_grid = true;
        }
        if($grid['columns'][$grid['level']]['absolute_current']==$grid['columns'][$grid['level']]['total']){
            $close_grid = true;
        }

        // @since 1.9 - keep original size on mobile devices
        if( !isset( $atts['hide_on_mobile'] ) ) $atts['hide_on_mobile'] = '';
        if( !isset( $atts['resize_disabled_mobile'] ) ) $atts['resize_disabled_mobile'] = '';
        if( !isset( $atts['hide_on_mobile_window'] ) ) $atts['hide_on_mobile_window'] = '';
        if( !isset( $atts['resize_disabled_mobile_window'] ) ) $atts['resize_disabled_mobile_window'] = '';

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';
        if( !isset( $atts['force_responsiveness_mobile_window'] ) ) $atts['force_responsiveness_mobile_window'] = '';


        $result .= '<div class="super-shortcode super_' . $sizes[$atts['size']][0] . ' super-column'.$atts['invisible'].' column-number-'.$grid['columns'][$grid['level']]['current'].' grid-level-'.$grid['level'].' ' . $class . ' ' . $atts['margin'] . ($atts['resize_disabled_mobile']==true ? ' super-not-responsive' : '') . ($atts['resize_disabled_mobile_window']==true ? ' super-not-responsive-window' : '') . ($atts['hide_on_mobile']==true ? ' super-hide-mobile' : '') . ($atts['hide_on_mobile_window']==true ? ' super-hide-mobile-window' : '') . ($atts['force_responsiveness_mobile_window']==true ? ' super-force-responsiveness-window' : '') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"' . $styles; 
        $result .= self::conditional_attributes( $atts );
        if( $atts['duplicate']=='enabled' ) {
            // @since   1.2.8    - make sure this data is set
            if( !isset( $atts['duplicate_limit'] ) ) $atts['duplicate_limit'] = 0;
            $result .= ' data-duplicate_limit="' . $atts['duplicate_limit'] . '"';

            // @since 1.3
            if( !isset( $atts['duplicate_dynamically'] ) ) $atts['duplicate_dynamically'] = '';
            if($atts['duplicate_dynamically']=='true') {
                $result .= ' data-duplicate_dynamically="' . $atts['duplicate_dynamically'] . '"';
            }
        }
        $result .= '>';

        // @since   1.3   - column custom padding
        $close_custom_padding = false;
        if( !isset( $atts['enable_padding'] ) ) $atts['enable_padding'] = '';
        if( $atts['enable_padding']=='true' ) {
            if( !isset( $atts['padding'] ) ) $atts['padding'] = '';
            if( $atts['padding']!='' ) {
                $close_custom_padding = true;
                $result .= '<div class="super-column-custom-padding" style="padding:' . $atts['padding'] . ';">';
            }
        }

        if( !empty( $inner ) ) {
            if( $atts['duplicate']=='enabled' ) {
                $result .= '<div class="super-shortcode super-duplicate-column-fields">';
            }
            $grid['level']++;
            $GLOBALS['super_grid_system'] = $grid;
            $GLOBALS['super_column_found'] = 0;
            foreach( $inner as $k => $v ) {
                if( $v['tag']=='column' ) $GLOBALS['super_column_found']++;
            }
            foreach( $inner as $k => $v ) {
                $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings, $entry_data );
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

        // @since   1.3   - column custom padding
        if( $close_custom_padding==true ) {
            $result .= '</div>';
        }

        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        if($close_grid==true){
            $grid[$grid['level']]['width'] = 0;
            $grid['columns'][$grid['level']]['current'] = 0;
            $result .= '</div>';
        }
        $GLOBALS['super_grid_system'] = $grid;
        return $result;
    }


    /** 
     *  Quantity field
     *
     *  @since      1.2.1
    */    
    public static function quantity_field( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        $atts['icon'] = '';
        $atts['validation'] = 'numeric';
        if( (!isset($atts['wrapper_width'])) || ($atts['wrapper_width']==0) ) $atts['wrapper_width'] = 50;
        if( !isset($settings['theme_field_size']) ) $settings['theme_field_size'] = 'medium';
        if($settings['theme_field_size']=='large') $atts['wrapper_width'] = $atts['wrapper_width']+20;
        if($settings['theme_field_size']=='huge') $atts['wrapper_width'] = $atts['wrapper_width']+30;

        $result = self::opening_tag( $tag, $atts );
        $result .= '<span class="super-minus-button super-noselect"><i>-</i></span>';
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<input class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="text"';
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
     *  Toggle field
     *
     *  @since      2.9.0
    */    
    public static function toggle_field( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        $atts['validation'] = 'empty';
        if( (!isset($atts['wrapper_width'])) || ($atts['wrapper_width']==0) ) $atts['wrapper_width'] = 70;
        if( ($settings['theme_hide_icons']=='no') && ($atts['icon']!='') ) {
            if( !isset($settings['theme_field_size']) ) $settings['theme_field_size'] = 'medium';
            $atts['wrapper_width'] = $atts['wrapper_width']+33;
            if($settings['theme_field_size']=='large') $atts['wrapper_width'] = $atts['wrapper_width']+20;
            if($settings['theme_field_size']=='huge') $atts['wrapper_width'] = $atts['wrapper_width']+40;
        }
        $result = self::opening_tag( $tag, $atts );
        
        if( ($atts['prefix_label']!='') || ($atts['prefix_tooltip']!='') ) {
            $result .= '<div class="super-toggle-prefix-label">';
            if($atts['prefix_label']!='') $result .= $atts['prefix_label'];
            if($atts['prefix_tooltip']!='') $result .= '<span class="super-toggle-prefix-question super-tooltip" title="' . esc_attr( stripslashes( $atts['prefix_tooltip'] ) ) . '"></span>';
            $result .= '</div>';
        }

        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since   1.1.8   - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }

        // @since   2.9.0 - autopopulate with last entry data
        if( isset( $entry_data[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $entry_data[$atts['name']]['value'] );
        }

        if( ( !isset( $atts['value'] ) ) || ( $atts['value']=='' ) ) $atts['value'] = '0';
        $result .= '<div class="super-toggle-switch ' . ( $atts['value']=='1' ? 'super-active' : '' ) . '">';
            $result .= '<div class="super-toggle-group">';
                $result .= '<label class="super-toggle-on" data-value="' . $atts['on_value'] . '">' . $atts['on_label'] . '</label>';
                $result .= '<label class="super-toggle-off" data-value="' . $atts['off_value'] . '">' . $atts['off_label'] . '</label>';
                $result .= '<span class="super-toggle-handle"></span>';
            $result .= '</div>';
        $result .= '</div>';

        $result .= '<input class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="hidden"';
        $result .= ' name="' . $atts['name'] . '" value="' . ( $atts['value']=='1' ? $atts['on_value'] : $atts['off_value'] ) . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        $result .= '</div>';

        if( ($atts['suffix_label']!='') || ($atts['suffix_tooltip']!='') ) {
            $result .= '<div class="super-toggle-suffix-label">';
            if($atts['suffix_label']!='') $result .= $atts['suffix_label'];
            if($atts['suffix_tooltip']!='') $result .= '<span class="super-toggle-suffix-question super-tooltip" title="' . esc_attr( stripslashes( $atts['suffix_tooltip'] ) ) . '"></span>';
            $result .= '</div>';
        }

        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }


    /** 
     *  Slider field
     *
     *  @since      1.2.1
    */    
    public static function slider_field( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        wp_enqueue_style('super-simpleslider', SUPER_PLUGIN_FILE.'assets/css/backend/simpleslider.min.css', array(), SUPER_VERSION);    
        wp_enqueue_script('super-simpleslider', SUPER_PLUGIN_FILE.'assets/js/backend/simpleslider.min.js', array(), SUPER_VERSION); 
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        $result .= '<input class="super-shortcode-field" type="text"';
        
        // @since   1.1.8   - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }

        // @since   2.9.0 - autopopulate with last entry data
        if( isset( $entry_data[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $entry_data[$atts['name']]['value'] );
        }

        if( ( !isset( $atts['value'] ) ) || ( $atts['value']=='' ) ) $atts['value'] = '';
        if( ( !isset( $atts['minnumber'] ) ) || ( $atts['minnumber']=='' ) ) $atts['minnumber'] = 0;
        if( ( !isset( $atts['maxnumber'] ) ) || ( $atts['maxnumber']=='' ) ) $atts['maxnumber'] = 100;
        
        if( !isset( $atts['format'] ) ) $atts['format'] = '';

        // @since 1.2.2
        if( !isset( $atts['currency'] ) ) $atts['currency'] = '$';
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

    /** 
     *  Currency field
     *
     *  @since      2.1.0
    */ 
    public static function currency( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        
        wp_enqueue_script( 'super-masked-currency', SUPER_PLUGIN_FILE . 'assets/js/frontend/masked-currency.min.js', array(), SUPER_VERSION ); 

        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        
        if( !isset( $atts['format'] ) ) $atts['format'] = '';
        if( !isset( $atts['currency'] ) ) $atts['currency'] = '$';
        if( !isset( $atts['decimals'] ) ) $atts['decimals'] = 2;
        if( !isset( $atts['thousand_separator'] ) ) $atts['thousand_separator'] = ',';
        if( !isset( $atts['decimal_separator'] ) ) $atts['decimal_separator'] = '.';

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<input class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="text"';

        // @since   1.1.8   - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }

        // @since   2.9.0 - autopopulate with last entry data
        if( isset( $entry_data[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $entry_data[$atts['name']]['value'] );
        }

        // @since   1.0.6   - make sure this data is set
        if( !isset( $atts['value'] ) ) {
            $atts['value'] = '';
        }
        if($atts['value']!='') $atts['value'] = SUPER_Common::email_tags( $atts['value'] );

        $result .= ' name="' . $atts['name'] . '" value="' . $atts['value'] . '" data-decimals="' . $atts['decimals'] . '" data-thousand-separator="' . $atts['thousand_separator'] . '" data-decimal-separator="' . $atts['decimal_separator'] . '" data-currency="' . $atts['currency'] . '" data-format="' . $atts['format'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        $result .= '<input type="hidden" value="' . str_replace($atts['thousand_separator'], "", $atts['value']) . '" />';

        
        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }

    public static function text( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
      
        // @since 3.0.0 - google places autocomplete/fill address based on user input
        $address_auto_populate_mappings = '';
        $address_auto_populate_class = '';
        if( !isset( $atts['enable_address_auto_complete'] ) ) $atts['enable_address_auto_complete'] = '';
        if( $atts['enable_address_auto_complete']=='true' ) {
            // Check if we need to auto populate fields with the retrieved data
            if( !isset( $atts['enable_address_auto_populate'] ) ) $atts['enable_address_auto_populate'] = '';
            if( $atts['enable_address_auto_populate']=='true' ) {
                $address_auto_populate_class = ' super-address-autopopulate';
                //onFocus="geolocate()"
                foreach($atts['address_auto_populate_mappings'] as $k => $v){
                    if($v['field']!='') $address_auto_populate_mappings .= ' data-map-' . $v['key'] . '="' . $v['field'] . '|' . $v['type'] . '"';
                }
            }
            if( !isset( $atts['address_api_key'] ) ) $atts['address_api_key'] = '';
            wp_enqueue_script( 'super-google-maps-api', 'https://maps.googleapis.com/maps/api/js?key=' . $atts['address_api_key'] . '&libraries=places&callback=SUPER.google_places.initAutocomplete', array( 'super-common' ), SUPER_VERSION, false );
        }

        // @since   1.2.4 - auto suggest feature
        if( !isset( $atts['enable_auto_suggest'] ) ) $atts['enable_auto_suggest'] = '';
        $class = ($atts['enable_auto_suggest']=='true' ? 'super-auto-suggest ' : '');

        // @since   3.1.0 - uppercase transformation
        if( !isset( $atts['uppercase'] ) ) $atts['uppercase'] = '';
        $class .= ($atts['uppercase']=='true' ? ' super-uppercase ' : '');

        $result = self::opening_tag( $tag, $atts, $class );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        
        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        // @since 2.9.0 - keyword enabled
        if( !isset( $atts['enable_keywords'] ) ) $atts['enable_keywords'] = '';
        if( !isset( $atts['keyword_split_method'] ) ) $atts['keyword_split_method'] = 'both';
        if( !isset( $atts['keyword_max'] ) ) $atts['keyword_max'] = 5;

        $result .= '<input class="super-shortcode-field' . $address_auto_populate_class . ($atts['class']!='' ? ' ' . $atts['class'] : '') . ($atts['enable_keywords']=='true' ? ' super-keyword' : '') . '" type="text"' . ($atts['enable_keywords']=='true' ? ' data-keyword-max="' . $atts['keyword_max'] . '" data-split-method="' . $atts['keyword_split_method'] . '"' : '');

        // @since   1.1.8 - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = sanitize_text_field( $_POST[$atts['name']] );
        }

        // @since   2.9.0 - autopopulate with last entry data
        if( isset( $entry_data[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $entry_data[$atts['name']]['value'] );
        }

        // @since   1.0.6   - make sure this data is set
        if( !isset( $atts['value'] ) ) {
            $atts['value'] = '';
        }
        if($atts['value']!='') $atts['value'] = SUPER_Common::email_tags( $atts['value'] );

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
                            $items[] = '<li data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '" class="selected super-default-selected">' . $v['label'] . '</li>'; 
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

        // @since   2.2.0   - search / populate with contact entry data
        if( !isset( $atts['enable_search'] ) ) $atts['enable_search'] = '';
        if( !isset( $atts['search_method'] ) ) $atts['search_method'] = 'equals';
        if( $atts['enable_search']=='true' ) {
            $result .= ' data-search="' . $atts['enable_search'] . '"';
            $result .= ' data-search-method="' . $atts['search_method'] . '"';

            // @since 3.1.0 - make sure if the parameter of this field element is set in the POST or GET we have to set the GET variables to auto fill the form fields based on the contact entry found
            if($atts['value']!=''){
                global $wpdb;
                $value = sanitize_text_field($atts['value']);
                $method = sanitize_text_field($atts['search_method']);
                $table = $wpdb->prefix . 'posts';
                $table_meta = $wpdb->prefix . 'postmeta';
                if($method=='equals') $query = "post_title = BINARY '$value'";
                if($method=='contains') $query = "post_title LIKE BINARY '%$value%'";
                $entry = $wpdb->get_results("SELECT ID FROM $table WHERE $query AND post_status IN ('publish','super_unread','super_read') AND post_type = 'super_contact_entry' LIMIT 1");
                $data = get_post_meta( $entry[0]->ID, '_super_contact_entry_data', true );
                unset($data['hidden_form_id']);
                if( isset($entry[0])) {
                    $data['hidden_contact_entry_id'] = array(
                        'name' => 'hidden_contact_entry_id',
                        'value' => $entry[0]->ID,
                        'type' => 'entry_id'
                    );
                }
                foreach($data as $k => $v){
                    $_GET[$k] = $v['value'];
                }
            }
        }
        
        // @since 3.0.0 - add data attributes to map google places data to specific fields
        if( $address_auto_populate_mappings!='' ) $result .= $address_auto_populate_mappings;
        
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';
        
        // @since 2.9.0 - entered keywords
        if( $atts['enable_keywords']=='true' ) {
            $result .= '<div class="super-entered-keywords"></div>';
        }

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
    public static function textarea( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        $result  = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        
        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }

        // @since   1.0.6   - make sure this data is set
        if( !isset( $atts['value'] ) ) {
            $atts['value'] = '';
        }
        if($atts['value']!='') $atts['value'] = SUPER_Common::email_tags( $atts['value'] );

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

            // @since 1.9 - custom class
            if( !isset( $atts['class'] ) ) $atts['class'] = '';

            $result .= '<textarea class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"';
            $result .= ' name="' . $atts['name'] . '"';
            $result .= self::common_attributes( $atts, $tag );
            $result .= ' >' . $atts['value'] . '</textarea>';
        }

        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function dropdown( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
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
                    $items[] = '<li data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '" class="selected super-default-selected">' . $v['label'] . '</li>'; 
                }else{
                    $items[] = '<li data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '">' . $v['label'] . '</li>'; 
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
                $items[] = '<li data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr( $v->name ) . '">' . $v->name . '</li>'; 
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
                        $items[] = '<li data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $title ) . '">' . $title . '</li>';
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

        // @since   2.9.0 - autopopulate with last entry data
        if( isset( $entry_data[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $entry_data[$atts['name']]['value'] );
        }

        $result .= '<input class="super-shortcode-field" type="hidden"';
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

        // @since 1.2.8     - auto scroll to value after key press
        $result .= '<input type="text" name="super-dropdown-search" value="" />';

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<ul class="super-dropdown-ui' . $multiple . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '">';
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
    public static function checkbox( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        $classes = ' display-' . $atts['display'];
        $result = self::opening_tag( $tag, $atts, $classes );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        $items = array();
        
        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }

        // @since   2.9.0 - autopopulate with last entry data
        if( isset( $entry_data[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $entry_data[$atts['name']]['value'] );
        }

        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        $checked_items = explode( ",", $atts['value'] );

        // @since   1.2.7
        if( !isset( $atts['retrieve_method'] ) ) $atts['retrieve_method'] = 'custom';
        if($atts['retrieve_method']=='custom') {
            foreach( $atts['checkbox_items'] as $k => $v ) {
                if( ( ($v['checked']==='true') || ($v['checked']===true) ) && ($atts['value']=='') ) $checked_items[] = $v['value'];
                if( !isset( $v['image'] ) ) $v['image'] = '';
                if( $v['image']!='' ) {
                    $image = wp_get_attachment_image_src( $v['image'], 'original' );
                    $image = !empty( $image[0] ) ? $image[0] : '';
                    $item = '';

                    // @since 3.0.0 - checkbox width and height setting
                    if( !isset( $v['max_width'] ) ) $v['max_width'] = 150;
                    if( !isset( $v['max_height'] ) ) $v['max_height'] = 200;
                    $img_styles = '';
                    if( $v['max_width']!='' ) $img_styles .= 'max-width:' . $v['max_width'] . 'px;';
                    if( $v['max_height']!='' ) $img_styles .= 'max-height:' . $v['max_height'] . 'px;';
                    
                    $item .= '<label class="' . ( !in_array($v['value'], $checked_items) ? ' super-has-image' : 'super-has-image super-selected super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '">';
                    if( !empty( $image ) ) {
                        $item .= '<div class="image" style="background-image:url(\'' . $image . '\');"><img src="' . $image . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                    }else{
                        $image = SUPER_PLUGIN_FILE . 'assets/images/image-icon.png';
                        $item .= '<div class="image" style="background-image:url(\'' . $image . '\');"><img src="' . $image . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                    }
                    $item .= '<input' . ( !in_array($v['value'], $checked_items) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $v['value'] ) . '" />';
                    if($v['label']!='') $item .= '<span class="super-item-label">' . $v['label'] . '</span>';
                    $item .='</label>';
                }else{
                    $item = '<label class="' . ( !in_array($v['value'], $checked_items) ? '' : 'super-selected super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input ' . ( (($v['checked']!=='true') && ($v['checked']!==true)) ? '' : 'checked="checked"' ) . ' type="checkbox" value="' . esc_attr( $v['value'] ) . '" />' . $v['label'] . '</label>';
                }
                $items[] = $item;
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
                $items[] = '<label class="' . ( !in_array($data_value, $checked_items) ? '' : 'super-selected super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input' . ( !in_array($data_value, $checked_items) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $data_value ) . '" />' . $v->name . '</label>';
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
                $items[] = '<label class="' . ( !in_array($data_value, $checked_items) ? '' : 'super-selected super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input' . ( !in_array($data_value, $checked_items) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $data_value ) . '" />' . $v->post_title . '</label>';
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
                        $items[] = '<label class="' . ( !in_array($value, $checked_items) ? '' : 'super-selected super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input' . ( !in_array($value, $checked_items) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $value ) . '" />' . $v->title . '</label>';
                    }
                    fclose($handle);
                }
            }
        }
        foreach( $items as $v ) {
            $result .= $v;
        }

        $result .= '<input class="super-shortcode-field" type="hidden"';
        $result .= ' name="' . esc_attr( $atts['name'] ) . '" value="' . implode(',',$checked_items) . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function radio( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        $classes = ' display-' . $atts['display'];
        $result = self::opening_tag( $tag, $atts, $classes );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        $items = array();
     
        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }

        // @since   2.9.0 - autopopulate with last entry data
        if( isset( $entry_data[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $entry_data[$atts['name']]['value'] );
        }

        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';     

        // @since   1.2.7
        if( !isset( $atts['retrieve_method'] ) ) $atts['retrieve_method'] = 'custom';
        if($atts['retrieve_method']=='custom') {
            $active_found = false;
            foreach( $atts['radio_items'] as $k => $v ) {
                if( ($v['checked']=='true') && ($atts['value']=='') ) $atts['value'] = $v['value'];

                // @since 2.6.0 - only 1 radio item can be active at a time
                $active = false;
                if( (($v['value']==$atts['value']) || ($v['checked']==='true') || ($v['checked']===true)) ) {
                    if($active_found==false){
                        $active_found = true;
                        $active = true;
                    }
                }
                
                // @since   1.2.3
                if( !isset( $v['image'] ) ) $v['image'] = '';
                if( $v['image']!='' ) {
                    $image = wp_get_attachment_image_src( $v['image'], 'original' );
                    $image = !empty( $image[0] ) ? $image[0] : '';
                    
                    // @since 3.0.0 - checkbox width and height setting
                    if( !isset( $v['max_width'] ) ) $v['max_width'] = 150;
                    if( !isset( $v['max_height'] ) ) $v['max_height'] = 200;
                    $img_styles = '';
                    if( $v['max_width']!='' ) $img_styles .= 'max-width:' . $v['max_width'] . 'px;';
                    if( $v['max_height']!='' ) $img_styles .= 'max-height:' . $v['max_height'] . 'px;';

                    $result .= '<label class="' . ( $active!=true ? ' super-has-image' : 'super-has-image super-selected super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '">';
                    if( !empty( $image ) ) {
                        $result .= '<div class="image" style="background-image:url(\'' . $image . '\');"><img src="' . $image . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                    }else{
                        $image = SUPER_PLUGIN_FILE . 'assets/images/image-icon.png';
                        $result .= '<div class="image" style="background-image:url(\'' . $image . '\');"><img src="' . $image . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                    }
                    $result .= '<input ' . ( (($v['checked']!=='true') && ($v['checked']!==true)) ? '' : 'checked="checked"' ) . ' type="radio" value="' . esc_attr( $v['value'] ) . '" />';
                    if($v['label']!='') $result .= '<span class="super-item-label">' . $v['label'] . '</span>';
                    $result .='</label>';

                }else{
                    $result .= '<label class="' . ( $active!=true ? '' : 'super-selected super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input ' . ( (($v['checked']!=='true') && ($v['checked']!==true)) ? '' : 'checked="checked"' ) . ' type="radio" value="' . esc_attr( $v['value'] ) . '" />' . $v['label'] . '</label>';
                }
            }
        }      

        // @since   1.7
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
                $items[] = '<label class="' . ( ($atts['value']!=$data_value) ? '' : 'super-selected super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input type="radio" value="' . esc_attr( $data_value ) . '" />' . $v->name . '</label>';
            }
        }

        // @since   1.7
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
                $items[] = '<label class="' . ( ($atts['value']!=$data_value) ? '' : 'super-selected super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input type="radio" value="' . esc_attr( $data_value ) . '" />' . $v->post_title . '</label>';
            }
        }

        // @since   1.7
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
                        $items[] = '<label class="' . ( ($atts['value']!=$value) ? '' : 'super-selected super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input type="radio" value="' . esc_attr( $value ) . '" />' . $title . '</label>';
                    }
                    fclose($handle);
                }
            }
        }
        foreach( $items as $v ) {
            $result .= $v;
        }

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
    public static function file( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        $dir = SUPER_PLUGIN_FILE . 'assets/js/frontend/jquery-file-upload/';
        wp_enqueue_script( 'jquery-ui-widget' );
        wp_enqueue_script( 'super-upload-iframe-transport', $dir . 'jquery.iframe-transport.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
        wp_enqueue_script( 'super-upload-fileupload', $dir . 'jquery.fileupload.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
        wp_enqueue_script( 'super-upload-fileupload-process', $dir . 'jquery.fileupload-process.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
        wp_enqueue_script( 'super-upload-fileupload-validate', $dir . 'jquery.fileupload-validate.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        
        // @since 1.2.8
        if( !isset( $atts['image'] ) ) $atts['image'] = '';
        if( !isset( $atts['enable_image_button'] ) ) $atts['enable_image_button'] = '';

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<div class="super-fileupload-button' . (($atts['enable_image_button']=='true' && $atts['image']!='')  ? ' super-fileupload-image' : '') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"';
        $style = '';
        if( !isset( $atts['extensions'] ) ) $atts['extensions'] = 'jpg|jpeg|png|gif|pdf';
        if( !isset( $atts['width'] ) ) $atts['width'] = 0;
        if( $atts['width']!=0 ) $style .= 'width:' . $atts['width'] . 'px;';
        if( !empty( $styles ) ) $style .= $styles;
        if( !empty( $style ) ) $result .= ' style="'.$style.'"';
        $result .= '><i class="fa fa-plus"></i><span class="super-fileupload-button-text">' . $atts['placeholder'] . '</span>';

        // @since 1.2.8
        if( ($atts['enable_image_button']=='true') && ($atts['image']!='') ) {
            $image = wp_prepare_attachment_for_js( $atts['image'] );
            $img_styles = '';
            if( !isset( $atts['max_img_width'] ) ) $atts['max_img_width'] = 0;
            if( !isset( $atts['max_img_height'] ) ) $atts['max_img_height'] = 0;
            if( $atts['max_img_width']>0 ) {
                $img_styles .= 'max-width:' . $atts['max_img_width'] . 'px;';
            }
            if( $atts['max_img_height']>0 ) {
                $img_styles .= 'max-height:' . $atts['max_img_height'] . 'px;';
            }
            if($img_styles!='') $img_styles = 'style="' . $img_styles . '" ';
            $result .= '<img src="' . $image['url'] . '" ' . $img_styles . 'alt="' . $image['alt'] . '" title="' . $image['title'] . '" />';
        }

        $result .= '</div>';
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
        $result .= '<div class="super-fileupload-files">';
            // @since   2.9.0 - autopopulate with last entry data
            if( ($entry_data!=null) && (isset($entry_data[$atts['name']])) ) {
                foreach( $entry_data[$atts['name']]['files'] as $k => $v ) {
                    $result .= '<div data-name="' . $v['value'] . '" class="super-uploaded"';
                    $result .= ' data-url="' . $v['url'] . '"';
                    $result .= ' data-thumburl="' . $v['thumburl'] . '">';
                    $result .= '<span class="super-fileupload-name"><a href="' . $v['url'] . '" target="_blank">' . $v['value'] . '</a></span>';
                    $result .= '<span class="super-fileupload-delete">[x]</span>';
                    $result .= '</div>';
                }
            }
        $result .= '</div>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function date( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        wp_enqueue_script( 'jquery-ui-datepicker', false, array( 'jquery' ), SUPER_VERSION );
        wp_enqueue_script( 'super-date-format', SUPER_PLUGIN_FILE . 'assets/js/frontend/date-format.min.js' );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<input class="super-shortcode-field super-datepicker' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="text" autocomplete="off" ';
        $format = $atts['format'];
        if( $format=='custom' ) $format = $atts['custom_format'];

        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }

        // @since   2.9.0 - autopopulate with last entry data
        if( isset( $entry_data[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $entry_data[$atts['name']]['value'] );
        }

        // @since 1.1.8 - added option to select an other datepicker to achieve date range with 2 datepickers (useful for booking forms)
        if( !isset( $atts['connected_min'] ) ) $atts['connected_min'] = '';
        if( !isset( $atts['connected_max'] ) ) $atts['connected_max'] = '';

        // @since 1.2.5 - added option to add or deduct days based on connected datepicker
        if( !isset( $atts['connected_min_days'] ) ) $atts['connected_min_days'] = '1';
        if( !isset( $atts['connected_max_days'] ) ) $atts['connected_max_days'] = '1';

        if( !isset( $atts['range'] ) ) $atts['range'] = '-100:+5';
        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        // @since 1.3 - Return the current date as default value
        if( !isset( $atts['current_date'] ) ) $atts['current_date'] = '';
        if( $atts['current_date']=='true' ) {
            $new_format = $format;
            if (preg_match("/dd/i", $new_format)) {
                $new_format = str_replace('dd', 'd', $new_format);
            }else{
                $new_format = str_replace('d', 'j', $new_format);
            }
            if (preg_match("/mm/i", $new_format)) {
                $new_format = str_replace('mm', 'm', $new_format);
            }else{
                $new_format = str_replace('m', 'n', $new_format);
            }
            if (preg_match("/oo/i", $new_format)) {
                $new_format = str_replace('oo', 'z', $new_format);
            }else{
                $new_format = str_replace('o', 'z', $new_format);
            }
            if (preg_match("/DD/i", $new_format)) {
                $new_format = str_replace('DD', 'l', $new_format);
            }
            if (preg_match("/MM/i", $new_format)) {
                $new_format = str_replace('MM', 'F', $new_format);
            }
            $new_format = str_replace('yy', 'Y', $new_format);
            $atts['value'] = date($new_format);
        }

        // @since 1.5 - Return weekends only
        if( !isset( $atts['work_days'] ) ) $atts['work_days'] = 'true';
        if( !isset( $atts['weekends'] ) ) $atts['weekends'] = 'true';

        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '" data-format="' . $format . '" data-work_days="' . $atts['work_days'] . '" data-weekends="' . $atts['weekends'] . '" data-connected_min="' . $atts['connected_min'] . '" data-connected_min_days="' . $atts['connected_min_days'] . '" data-connected_max="' . $atts['connected_max'] . '" data-connected_max_days="' . $atts['connected_max_days'] . '" data-range="' . $atts['range'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' readonly="true" />';

        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function time( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        wp_enqueue_script( 'jquery-timepicker', SUPER_PLUGIN_FILE . 'assets/js/frontend/timepicker.min.js' );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }

        // @since   2.9.0 - autopopulate with last entry data
        if( isset( $entry_data[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $entry_data[$atts['name']]['value'] );
        }

        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        // @since 1.3 - Return the current date as default value
        if( !isset( $atts['current_time'] ) ) $atts['current_time'] = '';
        if( $atts['current_time']=='true' ) {
            $atts['value'] = date($atts['format']);
        }

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<input class="super-shortcode-field super-timepicker' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="text" autocomplete="off" ';
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
    public static function rating( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        $result .= '<div class="super-rating">';

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }

        // @since   2.9.0 - autopopulate with last entry data
        if( isset( $entry_data[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $entry_data[$atts['name']]['value'] );
        }

        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        $i=1;
        while( $i < 6 ) {
            $result .= '<i class="fa fa-star super-rating-star ' . ($i<=$atts['value'] ? 'selected ' : '') . $atts['class'] . '"></i>';
            $i++;
        }

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
    public static function skype( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        wp_enqueue_script( 'super-skype', 'https://secure.skypeassets.com/i/scom/js/skype-uri.js' );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        if( !isset( $atts['username'] ) ) $atts['username'] = '';
        $result .= '<div id="SkypeButton_Call_' . $atts['username'] . '" class="super-skype-button" data-username="' . $atts['username'] . '" data-method="' . $atts['method'] . '" data-color="' . $atts['color'] . '" data-size="' . $atts['size'] . '"></div>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function countries( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
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

        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( isset( $atts['custom_regex'] ) ) $result .= self::custom_regex( $atts['custom_regex'] );

        // @since 1.2.8     - auto scroll to value after key press
        $result .= '<input type="text" name="super-dropdown-search" value="" />';

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<ul class="super-dropdown-ui' . $multiple . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '">';
        if( !empty( $atts['placeholder'] ) ) {
            $result .= '<li data-value="" class="super-placeholder">' . $atts['placeholder'] . '</li>';
        }else{
            $result .= '<li data-value="" class="super-placeholder"></li>';
        }
        


        // @since 1.1.4 - use wp_remote_fopen instead of curl
        $countries = array();
        if ( file_exists( SUPER_PLUGIN_DIR . '/countries.txt' ) ) {
            $countries = wp_remote_fopen( SUPER_PLUGIN_FILE . 'countries.txt' );
            $countries = explode( "\n", $countries );
        }

        if( isset($settings['form_button_loading']) ) $loading = $settings['form_button_loading'];

        // @since 2.8.0 - give the possibility to filter countries list (currently used by register & login add-on for woocommerce countries)
        $countries = apply_filters( 'super_countries_list_filter', $countries, array( 'name'=>$atts['name'], 'settings'=>$settings ) );

        foreach( $countries as $k => $v ){
            $v = trim($v);
            $result .= '<li data-value="' . ( is_string($k) ? esc_attr($k) : esc_attr($v) ) . '" data-search-value="' . esc_attr( $v ) . '">' . $v . '</li>'; 
        }
        $result .= '</ul>';
        $result .= '<span class="super-dropdown-arrow"></span>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function password( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since   1.1.8    - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }
        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<input class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="password"';
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

        // @since   3.0.0 - also allow tags for hidden fields 
        if( !isset( $atts['value'] ) ) {
            $atts['value'] = '';
        }
        if($atts['value']!='') $atts['value'] = SUPER_Common::email_tags( $atts['value'] );

        if( !isset( $atts['exclude'] ) ) $atts['exclude'] = 0;

        // @since 2.8.0 - invoice numbers
        if( !isset( $atts['code_invoice'] ) ) $atts['code_invoice'] = '';
        if( !isset( $atts['code_invoice_padding'] ) ) $atts['code_invoice_padding'] = '';

        // @since 2.2.0 - random code generation
        if( !isset( $atts['enable_random_code'] ) ) $atts['enable_random_code'] = '';
        if($atts['enable_random_code']=='true'){
            if( !isset( $atts['code_length'] ) ) $atts['code_length'] = 7;
            if( !isset( $atts['code_characters'] ) ) $atts['code_characters'] = '1';
            if( !isset( $atts['code_prefix'] ) ) $atts['code_prefix'] = '';
            if( !isset( $atts['code_suffix'] ) ) $atts['code_suffix'] = '';
            if( !isset( $atts['code_uppercase'] ) ) $atts['code_uppercase'] = '';
            if( !isset( $atts['code_lowercase'] ) ) $atts['code_lowercase'] = '';
            $atts['value'] = SUPER_Common::generate_random_code($atts['code_length'], $atts['code_characters'], $atts['code_prefix'], $atts['code_invoice'], $atts['code_invoice_padding'], $atts['code_suffix'], $atts['code_uppercase'], $atts['code_lowercase']);
        }

        $result .= '<input class="super-shortcode-field" type="hidden" value="' . $atts['value'] . '" name="' . $atts['name'] . '" data-email="' . $atts['email'] . '" data-exclude="' . $atts['exclude'] . '"' . ($atts['enable_random_code']=='true' ? ' data-code="' . $atts['enable_random_code'] . '"' : '') . ($atts['code_invoice']=='true' ? ' data-invoice-padding="' . $atts['code_invoice_padding'] . '"' : '') . ' />';
        $result .= self::loop_variable_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function image( $tag, $atts ) {
        $result = self::opening_tag( $tag, $atts, 'align-' . $atts['alignment'] );
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

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        if( ($image==null) || ($image['url']=='') ) {
            $image = SUPER_PLUGIN_FILE . 'assets/images/image-icon.png';
            $result .= '<img src="' . $image . '"' . ($atts['class']!='' ? ' class="' . $atts['class'] . '"' : '') . ' itemprop="contentURL"';
        }else{
            $result .= '<img src="' . $image['url'] . '"' . ($atts['class']!='' ? ' class="' . $atts['class'] . '"' : '') . ' alt="' . $image['alt'] . '" title="' . $image['title'] . '" itemprop="contentURL"';
        }
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

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result = self::opening_tag( $tag, $atts );
        if( $atts['title']!='' ) {
            $result .= '<div class="super-heading-title">';
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
            $result .= '<'.$atts['size'] . ($atts['class']!='' ? ' class="' . $atts['class'] . '"' : '') . ' style="'.$styles.'">';
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
            $result .= '<div class="super-heading-description' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" style="'.$styles.'">';
            $result .= $atts['desc'];
            $result .= '</div>';
        }
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }

    public static function html( $tag, $atts ) {

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result = self::opening_tag( $tag, $atts );
        if( !isset( $atts['title'] ) ) $atts['title'] = '';
        if( $atts['title']!='' ) {
            $class = '';
            if( ( $atts['subtitle']=='' ) && ( $atts['html']=='' ) ) {
                $class = ' super-bottom-margin';
            }
            $result .= '<div class="super-html-title' . $class . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '">' . stripslashes($atts['title']) . '</div>';
        }
        if( !isset( $atts['subtitle'] ) ) $atts['subtitle'] = '';
        if( $atts['subtitle']!='' ) {
            $class = '';
            if( $atts['html']!='' ) { 
                $class = ' super-no-bottom-margin'; 
            }
            $result .= '<div class="super-html-subtitle' . $class . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '">' . stripslashes($atts['subtitle']) . '</div>';
        }
        if( $atts['html']!='' ) { 
            
            // @since 2.3.0 - speed improvements for replacing {tags} in HTML fields
            preg_match_all('/{\K[^}]*(?=})/m', $atts['html'], $matches);
            $fields = implode('][', $matches[0]);

            $result .= '<div class="super-html-content' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" data-fields="[' . $fields . ']">' . do_shortcode( stripslashes($atts['html']) ) . '</div>';
            $result .= '<textarea>' . do_shortcode( stripslashes($atts['html']) ) . '</textarea>';
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

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<div class="super-divider-inner' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" style="' . $styles . '">';
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

        // Make sure the default form button won't be returned, since we are using a custom button
        if( !isset( $GLOBALS['super_custom_button_used'] ) ) {
            $GLOBALS['super_custom_button_used'] = true;
        }

        $name = $settings['form_button'];
        
        // @since 2.0.0 - button action (submit/clear/redirect)
        $action = 'submit';

        // @since 2.0.0 - button loading state text
        $loading = '';
        if( isset($settings['form_button_loading']) ) $loading = $settings['form_button_loading'];

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

        if( isset( $atts['action']) ) $action = $atts['action'];
        if( isset( $atts['name'] ) ) $name = $atts['name'];
        if( isset( $atts['loading'] ) ) $loading = $atts['loading'];
        
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

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';
        if( !isset( $atts['wrapper_class'] ) ) $atts['wrapper_class'] = '';
        if( $atts['wrapper_class']!='' ) $class .= ' ' . $atts['wrapper_class'];

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
            if( !isset( $atts['target'] ) ) $atts['target'] = '';
            if( !isset( $atts['action'] ) ) $atts['action'] = 'submit';
            $url = '';
            if($atts['action']!='submit'){
                if( !isset( $atts['link'] ) ) $atts['link'] = '';
                if( $atts['link']!='' ) {
                    if( $atts['link']=='custom' ) {
                        $url = $atts['custom_link'];
                    }else{
                        $url = get_permalink( $atts[$atts['link']] );
                    }
                }
                if( !empty( $atts['target'] ) ) $atts['target'] = 'target="' . $atts['target'] . '" ';
            }
            $result .= '<a ' . $atts['target'] . 'href="' . $url . '" class="no_link' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '">';
                $result .= '<div class="super-button-name" data-action="' . $action . '" data-loading="' . $loading . '">';
                    $icon_html = '';
                    if( ( $icon!='' ) && ( $icon_option!='none' ) ) {
                        $icon_html = '<i class="fa fa-' . $icon . '"></i>';
                    }
                    if( $icon_option=='left' ) $result .= $icon_html;
                    $result .= stripslashes($name);
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
    public static function output_element_html( $tag, $group, $data, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
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
        return call_user_func( array( $class, $function ), $tag, $data, $inner, $shortcodes, $settings, $entry_data );
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


    /** 
     *  The form shortcode that will generate all fields on the frontend
     *
     * @param  array $atts
     * @param  string $content
     *
     *  @since      1.0.0
    */
    public static function super_form_func( $atts ) {
        
        // @since 2.1.0 - make sure we reset the grid system
        unset($GLOBALS['super_grid_system']);

        extract( shortcode_atts( array(
            'id' => '',
        ), $atts ) );

        // Sanitize the ID
        $id = absint($id);

        // Check if the post exists
        if ( FALSE === get_post_status( $id ) ) {
            // The post does not exist
            $result = '<strong>'.__('Error', 'super-forms' ).':</strong> '.sprintf(__('Super Forms could not find a form with ID: %d', 'super-forms' ), $id);
            return $result;
        }else{
            // Check if the post is a super_form post type
            $post_type = get_post_type($id);
            if( $post_type!='super_form' ) {
                    $result = '<strong>'.__('Error', 'super-forms' ).':</strong> '.sprintf(__('Super Forms could not find a form with ID: %d', 'super-forms' ), $id);
                    return $result;
            }
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
        $settings = apply_filters( 'super_form_settings_filter', $settings, array( 'id'=>$id ) );
        SUPER_Forms()->enqueue_element_styles();
        SUPER_Forms()->enqueue_element_scripts($settings);

        // If post exists get the settings
        $styles = '';
        if( ( isset( $settings['theme_max_width'] ) ) && ( $settings['theme_max_width']!=0 ) ) {
            $styles .= 'max-width:' . $settings['theme_max_width'] . 'px;';
        }

        // @since 1.3
        if( ( isset( $settings['theme_form_margin'] ) ) && ( $settings['theme_form_margin']!='' ) ) {
            $styles .= 'margin:' . $settings['theme_form_margin'] . ';';
        }

        if($styles!='') $styles = 'style="' . $styles . '"';

        // Try to load the selected theme style
        $class = 'style-default';
        $style_content  = '';
        if( ( isset( $settings['theme_style'] ) ) && ( $settings['theme_style']!='' ) ) {
            $class .= ' ' . $settings['theme_style'];
            $style_content .= require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/' . str_replace( 'super-', '', $settings['theme_style'] ) . '.php' );
        }

        // @since 2.9.0 - theme field size in height
        if( !isset( $settings['theme_field_size'] ) ) $settings['theme_field_size'] = 'medium';
        $class .= ' super-field-size-' . $settings['theme_field_size'];

        // @since 1.2.4     - use transparent field background
        if( (isset( $settings['theme_field_transparent'] )) && ($settings['theme_field_transparent']=='true') ) {
            $class .= ' super-transparent-fields';
        }

        // @since 1.2.8     - RTL support
        if( (isset( $settings['theme_rtl'] )) && ($settings['theme_rtl']=='true') ) {
            $class .= ' super-rtl';
        }

        // Always load the default styles (these can be overwritten by the above loaded style file
        $style_content .= require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/style-default.php' );
      
        // @since 2.9.0 - autopopulate form with user last submitted entry data
        $entry_data = null;
        if( ( isset( $settings['retrieve_last_entry_data'] ) ) && ( $settings['retrieve_last_entry_data']=='true' ) ) {
            $current_user_id = get_current_user_id();
            if( $current_user_id!=0 ) {
                $form_ids = array($id);
                if( ( isset( $settings['retrieve_last_entry_form'] ) ) && ( $settings['retrieve_last_entry_form']!='' ) ) {
                    $form_ids = explode( ",", $settings['retrieve_last_entry_form'] );
                }
                $form_ids = implode("','", $form_ids);

                // First check if we can find contact entries based on user ID and Form ID
                global $wpdb;
                $table = $wpdb->prefix . 'posts';
                $table_meta = $wpdb->prefix . 'postmeta';
                $entry = $wpdb->get_results("
                SELECT  ID 
                FROM    $table 
                WHERE   post_author = $current_user_id AND
                        post_parent IN ($form_ids) AND
                        post_status IN ('publish','super_unread','super_read') AND 
                        post_type = 'super_contact_entry'
                ORDER BY ID DESC
                LIMIT 1");
                if( isset($entry[0])) {
                    $entry_data = get_post_meta( $entry[0]->ID, '_super_contact_entry_data', true );
                    unset($entry_data['hidden_form_id']);
                }
            }
        }

        $result = '';
        $result .= '<style type="text/css">.super-form-' . $id . ' > * {visibility:hidden;}</style>';
        $result .= '<div id="super-form-' . $id . '" ' . $styles . 'class="super-form ' . ( $settings['form_preload'] == 0 ? 'preload-disabled ' : '' ) . 'super-form-' . $id . ' ' . $class . '"' . ( (isset($settings['form_hide_after_submitting'])) && ($settings['form_hide_after_submitting']=='true') ? ' data-hide="true"' : '' ) . ( (isset($settings['form_clear_after_submitting'])) && ($settings['form_clear_after_submitting']=='true') ? ' data-clear="true"' : '' ) . ' data-field-size="' . $settings['theme_field_size'] . '">'; 
        
        // @since 1.8 - needed for autocomplete
        $result .= '<form autocomplete="on"';

        // @since 2.2.0 - custom POST method
        if( ( isset( $settings['form_post_option'] ) ) && ( $settings['form_post_option']=='true' ) ) {
            $result .= ' method="post" action="' . $settings['form_post_url'] . '">';
            $result .= '<textarea class="super-hidden" name="json_data"></textarea>';
        }else{
            $result .= '>';
        }

        // @since 3.1.0 - filter to add any HTML before the first form element
        $result = apply_filters( 'super_form_before_first_form_element_filter', $result, array( 'id'=>$id, 'settings'=>$settings ) );

        if( ( (isset($_REQUEST['action'])) && ($_REQUEST['action']!='super_load_preview') ) || ( !isset($_REQUEST['action']) ) ) {
            $sac = get_option( 'image_default_positioning', 0 );
            if( $sac!=1 ) {
                $result .= '<div class="super-msg super-error"><h1>Please note:</h1>';
                $result .= __( 'You haven\'t activated your Super Forms Plugin yet', 'super-forms' ).'<br />';
                $result .= __( 'Please click <a target="_blank" href="' . admin_url() . 'admin.php?page=super_settings#activate">here</a> and enter you Purchase Code under the Activation TAB.', 'super-forms' );
                $result .= '<span class="close"></span></div>';
                $result .= '</div>';
                return $result;
            }

            // @since 1.9
            $activation_msg = '';
            $activation_msg = apply_filters( 'super_after_activation_message_filter', $activation_msg, array( 'id'=>$id, 'settings'=>$settings ) );
            if( $activation_msg!='' ) {
                $result .= $activation_msg;
                $result .= '</form>';
                $result .= '</div>';   
                return $result;
            }
        }
        $result .= '<div class="super-shortcode super-field hidden">';
        $result .= '<input class="super-shortcode-field" type="hidden" value="' . $id . '" name="hidden_form_id" />';
        $result .= '</div>';

        // @since 2.2.0 - update contact entry by ID
        if( (isset( $settings['update_contact_entry'] )) && ($settings['update_contact_entry']=='true') ) {
            $contact_entry_id = 0;
            if( isset( $_GET['contact_entry_id'] ) ) {
                $contact_entry_id = $_GET['contact_entry_id'];
            }else{
                if( isset( $_POST['contact_entry_id'] ) ) {
                    $contact_entry_id = $_POST['contact_entry_id'];
                }
            }
            $result .= '<div class="super-shortcode super-field hidden">';
            $result .= '<input class="super-shortcode-field" type="hidden" value="' . absint($contact_entry_id) . '" name="hidden_contact_entry_id" />';
            $result .= '</div>';
        }

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
                $result .= self::output_element_html( $v->tag, $v->group, $v->data, $v->inner, $shortcodes, $settings, $entry_data );
            }
        }
        
        // Make sure to only return the default submit button if no custom button was used
        if(!isset($GLOBALS['super_custom_button_used'])){
            $result .= self::button( 'button', array(), '', '', $settings );
        }

        // Always unset after all elements have been processed
        unset($GLOBALS['super_custom_button_used']);
        unset($GLOBALS['super_first_multipart']); // @since 2.6.0

        // @since 3.1.0 - filter to add any HTML after the last form element
        $result = apply_filters( 'super_form_after_last_form_element_filter', $result, array( 'id'=>$id, 'settings'=>$settings ) );

        $result .= '</form>';


        // @since 3.0.0 - new loading method (gif stops/freezes animating when browser is doing javascript at background)
        $result .= '<span class="super-load-icon"></span>';

        $result .= '</div>';

        // @since 1.3   - put styles in global variable and append it to the footer at the very end
        SUPER_Forms()->form_custom_css .= apply_filters( 'super_form_styles_filter', $style_content, array( 'id'=>$id, 'settings'=>$settings ) );

        $settings_default = get_option( 'super_settings' );
        if( !isset( $settings_default['theme_custom_css'] ) ) $settings_default['theme_custom_css'] = '';
        $settings_default['theme_custom_css'] = stripslashes($settings_default['theme_custom_css']);
        SUPER_Forms()->form_custom_css .= $settings_default['theme_custom_css'];

        // @since 1.2.8     - Custom CSS per Form
        if( !isset( $settings['form_custom_css'] ) ) $settings['form_custom_css'] = '';
        $settings['form_custom_css'] = stripslashes($settings['form_custom_css']);
        SUPER_Forms()->form_custom_css .= $settings['form_custom_css'];

        if( SUPER_Forms()->form_custom_css!='' ) {
            $result .= '<style type="text/css">' . SUPER_Forms()->form_custom_css . '</style>';
        }

        $result = apply_filters( 'super_form_before_do_shortcode_filter', $result, array( 'id'=>$id, 'settings'=>$settings ) );
        return do_shortcode( $result );
    }

}

endif;