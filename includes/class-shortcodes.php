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

if(!class_exists('SUPER_Shortcodes')) :

/**
 * SUPER_Shortcodes
 */
class SUPER_Shortcodes {
        
    /** 
	 *	All the fields
	 *
	 *	Create an array with all the fields
	 *
	 *	@since		1.0.0
	*/
	public static function shortcodes( $shortcode=false, $attributes=false, $content=false ) {
		
        $attributes = stripslashes_deep($attributes);

        $attr = array( 
            'shortcode'=>$shortcode, 
            'attributes'=>$attributes, 
            'content'=>$content 
        );
            
        include_once( 'shortcodes/predefined-arrays.php' );
        
        $array = array();
        
        $array = apply_filters( 'super_shortcodes_start_filter', $array, $attr );
        
        /** 
         *	Layout Elements
         *
         *	@since		1.0.0
        */
        include_once( 'shortcodes/layout-elements.php' );
        $array = apply_filters( 'super_shortcodes_after_layout_elements_filter', $array, $attr );

        
        /** 
         *	Form Elements
         *
         *	@since		1.0.0
        */
        include_once( 'shortcodes/form-elements.php' );
        $array = apply_filters( 'super_shortcodes_after_form_elements_filter', $array, $attr );
        
        
        /** 
         *	Price Elements
         *
         *	@since		1.0.0
        */
        include_once( 'shortcodes/price-elements.php' );
        $array = apply_filters( 'super_shortcodes_after_price_elements_filter', $array, $attr );
        
        $array = apply_filters( 'super_shortcodes_end_filter', $array, $attr );

        return $array;
        
    }
    
    /** 
	 *	Output the element HTML on the builder page (create form) inside the preview area
	 *
     * @param  string  $tag
	 *
	 *	@since		1.0.0
	*/
    public static function output_builder_html( $tag, $group, $data, $inner, $shortcodes=null ) {
        
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
                foreach( $v['fields'] as $vk => $vv ) {
                    if( ($vv['default']!=='') && ($vv['default']!==0) ) {
                        $fields[$vk] = $vv['default'];
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
            $class .= ' super_'.$sizes[$data['size']][0] . ' ' . str_replace( 'column_', 'super_', $tag );
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
                    $result .= self::output_element_html( $tag, $group, $data, $inner, $shortcodes );
                }
                if( !empty( $inner ) ) {
                    foreach( $inner as $k => $v ) {
                        $result .= self::output_builder_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes );
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
        }
        return $result;
    }


    public static function opening_tag( $tag, $atts, $class='', $styles='' ) {        
        $style = '';
        if( !isset( $atts['width'] ) ) $atts['width'] = 0;
        if( $atts['width']!=0 ) $style .= 'width:' . $atts['width'] . 'px;';
        if( !empty( $atts['tooltip'] ) ) {
            wp_enqueue_style('super-tooltips', SUPER_PLUGIN_FILE.'assets/css/tooltips.css');    
            wp_enqueue_script('super-tooltips', SUPER_PLUGIN_FILE.'assets/js/tooltips.js');   
        }
        $result = '<div';
        if( ( $style!='' ) || ( $styles!='' ) ) $result .= ' style="' . $style . $styles . '"';
        $result .= ' class="super-shortcode super-field super-' . $tag;
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
        if( !isset( $atts['email'] ) ) $atts['email'] = '';
        if( !isset( $atts['exclude'] ) ) $atts['exclude'] = 0;
        if( !isset( $atts['maxlength'] ) ) $atts['maxlength'] = 0;
        if( !isset( $atts['minlength'] ) ) $atts['minlength'] = 0;
        $result = ' data-message="' . $atts['error'] . '" data-validation="'.$atts['validation'].'" data-email="'.$atts['email'].'" data-exclude="'.$atts['exclude'].'"';
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
            if( $atts['maxlength']>0 ) {
                $result .= ' data-maxlength="' . $atts['maxlength'] . '"';
            }
            if( $atts['minlength']>0 ) {
                $result .= ' data-minlength="' . $atts['minlength'] . '"';
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
    public static function multipart( $tag, $atts, $inner, $shortcodes ) {
        $result  = '';
        $result .= '<div class="super-shortcode super-' . $tag . '" data-step-name="' . $atts['step_name'] .'" data-step-description="' . $atts['step_description'] . '" data-icon="' . $atts['icon'] . '">';
        if( !empty( $inner ) ) {
            foreach( $inner as $k => $v ) {
                $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes );
            }
        }
        $result .= '</div>';
        return $result;


    }
    public static function column( $tag, $atts, $inner, $shortcodes ) {
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
        $class = '';
        $result  = '';
        $close_grid = false;
        if( !isset( $GLOBALS['super_grid_row_counter'] ) ) {
            $GLOBALS['super_grid_row_counter'] = 0;
        }
        $counter = $GLOBALS['super_grid_row_counter'];
        if(!isset($GLOBALS['super_grid_total_width'][$counter])){
            $class = 'first-column';
            $result .= '<div class="super-grid super-shortcode">';
            $GLOBALS['super_grid_total_width'][$counter] = $sizes[$atts['size']][1];
            if($GLOBALS['super_grid_total_width'][$counter]>=100){
                $GLOBALS['super_grid_row_counter']++;
                $close_grid = true;
            }
        }else{
            $GLOBALS['super_grid_total_width'][$counter] = $GLOBALS['super_grid_total_width'][$counter]+$sizes[$atts['size']][1];
            if($GLOBALS['super_grid_total_width'][$counter]>=100){
                $class = 'last-column';
                $GLOBALS['super_grid_row_counter']++;
                $close_grid = true;
            }
        }
        $result .= '<div class="super-shortcode super_'.$sizes[$atts['size']][0].' wide column '.$class.' '.$atts['margin'].'"'; 
        $result .= self::conditional_attributes( $atts );
        $result .= '>';
        if( !empty( $inner ) ) {
            foreach( $inner as $k => $v ) {
                $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes );
            }
        }
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        if( $close_grid==true ) {
            $result .= '</div>';
        }
        return $result;
    }
    public static function text( $tag, $atts ) {
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts );
        $result .= '<input class="super-shortcode-field" type="text"';
        $result .= ' name="' . $atts['name'] . '" value=""';
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
        $result .= '<textarea class="super-shortcode-field"';
        $result .= ' name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' /></textarea>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function dropdown( $tag, $atts ) {
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts );
        $multiple = '';
        if( !isset( $atts['minlength'] ) ) $atts['minlength'] = 0;
        if( $atts['minlength']>1 ) {
            $multiple = ' multiple';
        }
        $result .= '<input class="super-shortcode-field" type="hidden"';
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';
        $result .= '<ul class="super-dropdown-ui' . $multiple . '">';
        if( !empty( $atts['placeholder'] ) ) {
            $result .= '<li data-value="" class="super-placeholder">' . $atts['placeholder'] . '</li>';
        }else{
            $result .= '<li data-value="" class="super-placeholder"></li>';
        }
        foreach( $atts['dropdown_items'] as $k => $v ) {
            $result .= '<li data-value="' . esc_attr( $v['value'] ) . '">' . $v['label'] . '</li>'; 
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
            $result .= '<label><input ' . ( (($v['checked']=='false') || ($v['checked']==false)) ? '' : 'checked="checked"' ) . ' type="checkbox" value="' . esc_attr( $v['value'] ) . '" />' . $v['label'] . '</label>';
        }
        $result .= '<input type="hidden" name="' . $atts['name'] . '" ';   
        $result .= '<input class="super-shortcode-field" type="hidden"';
        $result .= ' name="' . $atts['name'] . '" value=""';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function checkbox_items( $tag, $atts ) {
        return '<label><input ' . ( (($atts['checked']=='false') || ($atts['checked']==false)) ? '' : 'checked="checked"' ) . ' type="checkbox" value="' . esc_attr( $atts['value'] ) . '" />' . $atts['label'] . '</label>';
    }
    public static function radio( $tag, $atts ) {
        $classes = ' display-' . $atts['display'];
        $result = self::opening_tag( $tag, $atts, $classes );
        $result .= self::opening_wrapper( $atts );
        foreach( $atts['radio_items'] as $k => $v ) {
            $result .= '<label><input ' . ( (($v['checked']=='false') || ($v['checked']==false)) ? '' : 'checked="checked"' ) . ' type="radio" value="' . esc_attr( $v['value'] ) . '" />' . $v['label'] . '</label>';
        }
        $result .= '<input type="hidden" name="' . $atts['name'] . '" ';   
        $result .= '<input class="super-shortcode-field" type="hidden"';
        $result .= ' name="' . $atts['name'] . '" value=""';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function radio_items( $tag, $atts ) {
        return '<label><input ' . ( (($atts['checked']=='false') || ($atts['checked']==false)) ? '' : 'checked="checked"' ) . ' type="radio" value="' . esc_attr( $atts['value'] ) . '" />' . $atts['label'] . '</label>';
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
        if( $atts['maxlength']>1 ) $result .= ' multiple';
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
        wp_enqueue_script( 'jquery-ui-datepicker', false, array( 'jquery' ) );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts );
        $result .= '<input class="super-shortcode-field super-datepicker" type="text" autocomplete="off" ';
        $result .= ' value="" name="' . $atts['name'] . '" data-format="' . $atts['format'] . '"';
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
        $result .= '<input class="super-shortcode-field super-timepicker" type="text" autocomplete="off" ';
        if( !isset( $atts['range'] ) ) $atts['range'] = '';
        $result .= ' value="" name="' . $atts['name'] . '" data-format="' . $atts['format'] . '" data-step="' . $atts['step'] . '" data-range="' . $atts['range'] . '" data-duration="' . $atts['duration'] . '"';
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
        $result .= '<input class="super-shortcode-field super-star-rating" type="hidden"';
        $result .= ' value="" name="' . $atts['name'] . '"';
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
        if( !isset( $atts['minlength'] ) ) $atts['minlength'] = 0;
        if( $atts['minlength']>1 ) {
            $multiple = ' multiple';
        }
        $result .= '<input class="super-shortcode-field" type="hidden"';
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';
        $result .= '<ul class="super-dropdown-ui' . $multiple . '">';
        if( !empty( $atts['placeholder'] ) ) {
            $result .= '<li data-value="" class="super-placeholder">' . $atts['placeholder'] . '</li>';
        }else{
            $result .= '<li data-value="" class="super-placeholder"></li>';
        }
        $countries = file_get_contents( SUPER_PLUGIN_FILE . 'countries.txt', FILE_USE_INCLUDE_PATH );
        $countries = explode( "\n", $countries );
        asort( $countries );
        foreach( $countries as $k => $v ){
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
        $result .= '<input class="super-shortcode-field" type="password"';
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
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
        $result .= '<div class="super-recaptcha" data-key="' . $settings['form_recaptcha'] . '" data-message="' . $atts['error'] . '"></div>';
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
     *  Output the shortcode element on backend create form page under Tabs: Layout / Form Elements etc.
     *
     * @param  string  $tag
     * @param  string  $group
     * @param  array   $data
     * @param  array   $shortcodes
     *
     *  @since      1.0.0
    */
    public static function output_element_html( $tag, $group, $data, $inner, $shortcodes=null ) {
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
        return call_user_func( array( $class, $function ), $tag, $data, $inner, $shortcodes );
    }

    
    /** 
	 *	Output the shortcode element on backend create form page under Tabs: Layout / Form Elements etc.
	 *
     * @param  string $shortcode
     * @param  array $value
	 *
	 *	@since		1.0.0
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

        $settings = get_post_meta($id, '_super_form_settings', true );

        //wp_enqueue_script('super-validation', SUPER_PLUGIN_FILE.'assets/js/validation.min.js', array('jquery'), '1.0', false);  
        wp_enqueue_style( 'super-font-awesome', SUPER_PLUGIN_FILE . 'assets/css/fonts/font-awesome.min.css' );
        wp_enqueue_style( 'super-elements', SUPER_PLUGIN_FILE . 'assets/css/frontend/elements.min.css' );

        $handle = 'super-common';
        $name = str_replace( '-', '_', $handle ) . '_i18n';
        wp_register_script( $handle, SUPER_PLUGIN_FILE . 'assets/js/common.min.js', array( 'jquery' ), '1.0', false );  
        wp_localize_script( $handle, $name, array( 'ajaxurl'=>SUPER_Forms()->ajax_url(), 'preload'=>$settings['form_preload'], 'duration'=>$settings['form_duration'] ) );
        wp_enqueue_script( $handle );

        wp_enqueue_script( 'super-elements', SUPER_PLUGIN_FILE . 'assets/js/frontend/elements.min.js', array( 'super-common' ), '1.0', false );  
        wp_enqueue_script( 'super-frontend-common', SUPER_PLUGIN_FILE . 'assets/js/frontend/common.min.js', array( 'super-common' ), '1.0', false );  


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
            $style_content .= require_once( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/' . str_replace( 'super-', '', $settings['theme_style'] ) . '.php' );
        }

        // Always load the default styles (these can be overwritten by the above loaded style file
        $style_content .= require_once( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/style-default.php' );
        
        $result = '';
        $result .= '<div ' . $theme_styles . 'class="super-form ' . ( $settings['form_preload'] == 0 ? 'active ' : '' ) . 'super-form-' . $id . ' ' . $theme_style . '">'; 
        $result .= '<div class="super-shortcode super-field hidden">';
        $result .= '<input class="super-shortcode-field" type="hidden" value="'.$id.'" name="hidden_form_id" />';
        $result .= '</div>';
        
        // Loop through all form elements
        $elements = json_decode( get_post_meta( $id, '_super_elements', true ) );
        if( !empty( $elements ) ) {
            $shortcodes = self::shortcodes();
            foreach( $elements as $k => $v ) {
                $result .= self::output_element_html( $v->tag, $v->group, $v->data, $v->inner, $shortcodes );
            }
        }

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
        $class = 'super-extra-shortcode super-shortcode super-field super-form-button ';
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
        $button = '';
        $button .= '<div' . $attributes . ' data-radius="' . $radius . '" data-type="' . $type . '" class="' . $class . '">';
        $button .= '<a href="#">';
        $button .= '<div class="super-button-name">';
        if( ( $icon!='' ) && ( $icon_option!='none' ) ) {
            $button .= '<i class="fa fa-' . $icon . '"></i>';
        }
        $button .= $name;
        $button .= '</div>';
        $button .= '<span class="super-after"></span>';
        $button .= '</a>';
        $button .= '</div>';
        $result .= $button;
        $result .= '</div>';
        $settings = get_option('super_settings');
        $result .= '<style type="text/css">' . $style_content . $settings['theme_custom_css'] . '</style>';        
        return do_shortcode( $result );
    }    
    
    /** 
	 *	Common fields for each shortcode (backend dialog)
	 *
	 *	@since		1.0.0
	*/
    public static function name( $attributes=null, $default='' ) {
        $array = array(
            'name'=>__( 'Unique field name', 'super' ), 
            'desc'=>__( 'Must be an unique name (required)', 'super' ),
            'default'=> ( !isset( $attributes['name'] ) ? $default : $attributes['name'] ),
            'required'=>true,
        );
        return $array;
    }
    public static function email( $attributes=null, $default='' ) {
        $array = array(
            'name'=>__( 'Email Label', 'super' ), 
            'desc'=>__( 'Indicates the field in the email template. (required)', 'super' ),
            'default'=> ( !isset( $attributes['email'] ) ? $default : $attributes['email'] ),
            'required'=>true,
        );
        return $array;
    }
    public static function label( $attributes=null, $default='' ) {
        $array = array(
            'name'=>__( 'Field Label', 'super' ), 
            'desc'=>__( 'Will be visible in front of your field.', 'super' ).' ('.__( 'leave blank to remove', 'super' ).')',
            'default'=> ( !isset( $attributes['label'] ) ? $default : $attributes['label'] ),
        );
        return $array;
    }    
    public static function description( $attributes=null, $default='') {
        $array = array(
            'name'=>__( 'Field description', 'super' ), 
            'desc'=>__( 'Will be visible in front of your field.', 'super' ).' ('.__( 'leave blank to remove', 'super' ).')',
            'default'=> ( !isset( $attributes['description'] ) ? $default : $attributes['description'] ),
        );
        return $array;
    }
    public static function icon( $attributes=null, $default='user' ) {
        $icon = array(
            'default'=> ( !isset( $attributes['icon'] ) ? $default : $attributes['icon'] ),
            'name'=>__( 'Select an Icon', 'super' ), 
            'type'=>'icon',
            'desc'=>__( 'Leave blank if you prefer to not use an icon.', 'super' )
        );
        return $icon;
    }
    public static function placeholder( $attributes=null, $default=null ) {
        $array = array(
            'default'=> ( !isset( $attributes['placeholder'] ) ? $default : $attributes['placeholder'] ),
            'name'=>__( 'Placeholder', 'super' ), 
            'desc'=>__( 'Indicate what the user needs to enter or select. (leave blank to remove)', 'super' )
        );
        return $array;
    }    
    public static function width( $attributes=null, $default=0, $min=0, $max=600, $steps=10, $name=null, $desc=null ) {
        if( empty( $name ) ) $name = __( 'Field width in pixels', 'super' );
        if( empty( $desc ) ) $desc = __( 'Set to 0 to use default CSS width.', 'super' );
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
        if( empty( $name ) ) $name = __( 'Field width in pixels', 'super' );
        if( empty( $desc ) ) $desc = __( 'Set to 0 to use default CSS width.', 'super' );
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
        if( empty($name ) ) $name = __( 'Min characters/selections allowed', 'super' );
        if( empty($desc ) ) $desc = __( 'Set to 0 to remove limitations.', 'super' );
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
        if( empty( $name ) ) $name = __( 'Max characters/selections allowed', 'super' );
        if( empty( $desc ) ) $desc = __( 'Set to 0 to remove limitations.', 'super' );
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