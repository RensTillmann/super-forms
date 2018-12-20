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
     * @var string
     *
     *  @since      3.5.0
    */
    public static $current_form_id = 0;

    public static $shortcodes = false;


    /** 
     *  All the fields
     *
     *  Create an array with all the fields
     *
     *  @since      1.0.0
    */
    public static function shortcodes( $shortcode=false, $attributes=false, $content=false ) {
        
        // @since 3.4.0  - custom contact entry status
        $entry_statuses = SUPER_Settings::get_entry_statuses();
        $statuses = array();
        foreach($entry_statuses as $k => $v){
            $statuses[$k] = $v['name'];
        }
        
        $attributes = stripslashes_deep($attributes);

        $form_id = 0;
        if( (isset($_GET['id'])) && (isset($_GET['page'])) && ($_GET['page']=='super_create_form') ) {
            $form_id = absint($_GET['id']);
        }

        $attr = array( 
            'shortcode'=>$shortcode, 
            'attributes'=>$attributes, 
            'content'=>$content,
            'form_id'=>$form_id
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
        //include( 'shortcodes/form-elements.php' );
        //$array = apply_filters( 'super_shortcodes_after_form_elements_filter', $array, $attr );
        

        /** 
         *  HTML Elements
         *
         *  @since      3.5.0
        */
        include( 'shortcodes/html-elements.php' );
        $array = apply_filters( 'super_shortcodes_after_html_elements_filter', $array, $attr );

        
        /** 
         *  Price Elements
         *
         *  @since      1.0.0
        */
        include( 'shortcodes/price-elements.php' );
        $array = apply_filters( 'super_shortcodes_after_price_elements_filter', $array, $attr );
        
        $array = apply_filters( 'super_shortcodes_end_filter', $array, $attr );

        self::$shortcodes = $array;

        return $array;
        
    }
    
    /** 
     *  Output the element HTML on the builder page (create form) inside the preview area
     *
     * @param  string  $tag
     *
     *  @since      1.0.0
    */
    public static function output_builder_html( $tag, $group, $data, $inner, $shortcodes=null, $settings=null, $predefined=false ) {
        
        // @since 3.5.0 - backwards compatibility with older form codes that have image field and other HTML field in group form_elements instead of html_elements
        if( ($group=='form_elements') && ($tag=='image' || $tag=='heading' || $tag=='html' || $tag=='divider' || $tag=='spacer' || $tag=='google_map' ) ) {
            $group = 'html_elements';
        }

        if( $shortcodes==null ) {
            $shortcodes = self::shortcodes();
        }

        if( !isset( $shortcodes[$group]['shortcodes'][$tag] ) ) {
            return '';
        }

        $name = $shortcodes[$group]['shortcodes'][$tag]['name'];

        $data =  (array) $data;
        if( count($data)==0 ) {
            // We have to add the predefined values for each field setting
            $data = array();
            if(is_array($shortcodes[$group]['shortcodes'][$tag]['atts'])){
                foreach( $shortcodes[$group]['shortcodes'][$tag]['atts'] as $k => $v ) {
                    foreach( $v['fields'] as $fk => $fv ) {
                        if( $fv['default']!=='' ) {
                            $data[$fk] = $fv['default'];
                        }
                    }
                }
            }
        }else{
            // Skip this if we are adding a predefined element, otherwise it would override it with the element defaults
            if( $predefined==false ) {
                $data = json_decode(json_encode($data), true);
                foreach( $shortcodes[$group]['shortcodes'][$tag]['atts'] as $k => $v ) {
                    foreach( $v['fields'] as $fk => $fv ) {
                        if( isset($data[$fk]) ) {
                            $default = SUPER_Common::get_default_element_setting_value($shortcodes, $group, $tag, $k, $fk);
                            if( ( ($data[$fk]==$default) || ($data[$fk]==='') ) && (empty($v['fields'][$fk]['allow_empty'])) ) {
                                unset($data[$fk]);
                            }
                        }
                    }
                }
            }
        }

        $inner = json_decode(json_encode($inner), true);

        $class = '';
        $inner_class = '';

        if( $tag=='column' ) {
            if(empty($data['size'])) $data['size'] = '1/1';
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
                'custom'=>array('custom','custom'),
            ); 
            if( empty($data['width']) ) {
                $class .= ' super_'.$sizes[$data['size']][0];
            }
            $class .= ' super-' . str_replace( 'column_', 'super_', $tag );
        }
        if($tag=='multipart'){
            $class .= ' ' . $tag;
        }
  
        if( isset( $shortcodes[$group]['shortcodes'][$tag]['drop'] ) ) {
            $class .= ' drop-here';
            $inner_class .= ' super-dropable';
        }
        
        if( (!empty($data['minimized'])) && ($data['minimized']=='yes') ) {
            $class .= ' super-minimized';
        }else{
            unset($data['minimized']);
        }

        $result = '';
        $styles = '';
        $attributes = '';
        if( $tag=='column' ) {

            if( !empty($data['radius']) ) {
                $order = array('top_left', 'top_right', 'bottom_right', 'bottom_left');
                $radius = array();
                $radius_values = explode(' ', $data['radius']);
                foreach( $order as $k => $v) {
                    if( !isset($radius_values[$k]) ) {
                        $radius_values[$k] = '0px';
                    }else{
                        if( (!preg_match("/px/i", $radius_values[$k])) && (!preg_match("/%/i", $radius_values[$k])) ) {
                            $radius[$k] = $radius_values[$k].'px';
                        }else{
                            $radius[$k] = $radius_values[$k];
                        }
                    }
                }

                $identical = count(array_unique($radius))==1 ? true : false;
                $final_radius_css = '';
                if($identical){
                    if($radius[0]!=0){
                        $final_radius_css .= '-webkit-border-radius: '. $radius[0] . ';';
                        $final_radius_css .= '-moz-border-radius: '. $radius[0] . ';';
                        $final_radius_css .= 'border-radius: '. $radius[0] . ';';
                    }
                }else{
                    $final_radius_css .= '-webkit-border-top-left-radius: '. $radius[0] . ';';
                    $final_radius_css .= '-moz-border-radius-topleft: '. $radius[0] . ';';
                    $final_radius_css .= 'border-top-left-radius: '. $radius[0] . ';';

                    $final_radius_css .= '-webkit-border-top-right-radius: '. $radius[1] . ';';
                    $final_radius_css .= '-moz-border-radius-topright: '. $radius[1] . ';';
                    $final_radius_css .= 'border-top-right-radius: '. $radius[1] . ';';
                    
                    $final_radius_css .= '-webkit-border-bottom-right-radius: '. $radius[2] . ';';
                    $final_radius_css .= '-moz-border-radius-bottomright: '. $radius[2] . ';';
                    $final_radius_css .= 'border-bottom-right-radius: '. $radius[2] . ';';

                    $final_radius_css .= '-webkit-border-bottom-left-radius: '. $radius[3] . ';';
                    $final_radius_css .= '-moz-border-radius-bottomleft: '. $radius[3] . ';';
                    $final_radius_css .= 'border-bottom-left-radius: '. $radius[3] . ';';
                }
                if($final_radius_css!=''){
                    $styles .= $final_radius_css;
                }
            }

            if( !empty($data['margin']) ) {
                $order = array('top', 'right', 'bottom', 'left');
                $margins = array();
                $margin_values = explode(' ', $data['margin']);
                foreach( $order as $k => $v) {
                    if( !isset($margin_values[$k]) ) {
                        $margin_values[$k] = '0px';
                    }else{
                        if( (!preg_match("/px/i", $margin_values[$k])) && (!preg_match("/%/i", $margin_values[$k])) ) {
                            $margins[$k] = $margin_values[$k].'px';
                        }else{
                            $margins[$k] = $margin_values[$k];
                        }
                    }
                }
                $styles .= 'margin:' . implode(" ", $margins) . ';';
            }
            if( !empty($data['width']) ) {
                if( empty($data['width_unit']) ) $data['width_unit'] = 'px';
                $styles .= 'width:' . $data['width'] . $data['width_unit'] . ';';
                $attributes .= ' data-width="' . $data['width'] . $data['width_unit'] . '"';
            }else{
                $attributes .= ' data-size="' . $data['size'] . '"';
            }
            if( !empty($data['height']) ) {
                $data['height'] = strtolower(trim($data['height']));
                if( (!preg_match("/px/i", $data['height'])) && (!preg_match("/%/i", $data['height'])) ) {
                    $data['height'] = str_replace('px', '', $data['height']).'px';
                }else{
                    if( preg_match("/%/i", $data['height']) ) {
                        $data['height'] = str_replace('px', '', $data['height']);
                        $data['height'] = str_replace('%', '', $data['height']).'%';
                    }else{
                        $data['height'] = str_replace('px', '', $data['height']);
                        $data['height'] = str_replace('%', '', $data['height']).'px';;
                    }
                }
                $styles .= 'height:' . $data['height'] . ';';
                $attributes .= ' data-height="' . $data['height'] . '"';
            }
            if( !empty($data['bg_color']) ) {
                if( !isset( $data['bg_opacity'] ) ) $data['bg_opacity'] = 1;
                $styles .= 'background-color:' . SUPER_Common::hex2rgb( $data['bg_color'], $data['bg_opacity'] ) . ';';
            }
            if( !empty($data['bg_image']) ) {
                $image = wp_get_attachment_image_src( $data['bg_image'], 'original' );
                $image = !empty( $image[0] ) ? $image[0] : '';
                $image = wp_make_link_relative( $image );
                if( !empty( $image ) ) {
                    $styles .= 'background-image: url(' . $image . ');';
                }
                if( !empty($data['bg_size']) ) {
                    $styles .= 'background-size: ' . $data['bg_size'] . ';';
                }
                if( !empty($data['bg_repeat']) ) {
                    $styles .= 'background-repeat: ' . $data['bg_repeat'] . ';';
                }
                if( !empty($data['bg_position']) ) {
                    $styles .= 'background-position: ' . $data['bg_position'] . ';';
                }
            }

        }
        if( !empty($styles) ) {
            $styles = 'style="' . $styles . '"';
        }

        $attributes .= ' data-shortcode-tag="' . $tag . '"';
        $attributes .= ' data-group="'.$group.'"';
        $attributes .= ' data-minimized="' . ( !empty($data['minimized']) ? 'yes' : 'no' ) . '"';

        $result .= '<div class="super-element' . $class . '"' . $attributes . $styles . '>';
            if( ($tag!='column') && ($tag!='button') && ($tag!='button') ) {
                if(!isset($data['name'])) $data['name'] = $tag;
                $result .= '<div class="super-element-title">';
                    $result .= '<div class="super-title">';
                    $result .= ' <input class="super-tooltip" title="Unique field name" type="text" value="' . esc_attr($data['name']) . '" autocomplete="off" />';
                    $result .= '</div>';
                $result .= '</div>';                
            }

            $result .= '<div class="super-element-header">';
                if( ($tag=='column') || ($tag=='multipart') ){
                    $result .= '<div class="super-element-label">';
                    if(empty($data['label'])){
                        $label = $name;
                    }else{
                        $label = $data['label'];
                    } 
                    $result .= '<span>'.$label.'</span>';
                    $result .= '<input type="text" value="'.$label.'" />';
                    $result .= '</div>';
                }
                if($tag=='column'){
                    $result .= '<div class="resize super-tooltip" data-content="Change Column Size">';
                        $result .= '<span class="smaller"><i class="fa fa-angle-left"></i></span>';
                        if( $data['size']=='custom' ) {
                            if(!isset($data['width'])) $data['width'] = 0;
                            if(!isset($data['height'])) $data['height'] = 0;
                            $width = $data['width'];
                            $height = $data['height'];
                            if($width==0){
                                $width_text = 'auto';
                            }else{
                                $width_text = $width . '%';
                            }
                            if($height==0){
                                $height_text = 'auto';
                            }else{
                                $height_text = $height . 'px';
                            }
                            $size_text = $width_text . ' x ' . $height_text;
                        }else{
                            $size_text = $data['size'];
                        }
                        $result .= '<span class="current">' . $size_text . '</span><span class="bigger"><i class="fa fa-angle-right"></i></span>';
                    $result .= '</div>';
                }
                $result .= '<div class="super-element-actions">';
                    $result .= '<span class="edit super-tooltip" title="Edit element"><i class="fa fa-pencil"></i></span>';
                    $result .= '<span class="duplicate super-tooltip" title="Duplicate element"><i class="fa fa-files-o"></i></span>';
                    $result .= '<span class="move super-tooltip" title="Reposition element"><i class="fa fa-arrows"></i></span>';
                    $result .= '<span class="minimize super-tooltip" title="Minimize"><i class="fa fa-minus-square-o"></i></span>';
                    $result .= '<span class="delete super-tooltip" title="Delete"><i class="fa fa-times"></i></span>';
                $result .= '</div>';
            $result .= '</div>';


            $inner_styles = '';
            if( $tag=='column' ) {
                if( ($data['size']=='custom') && ($data['height']!=0) ) {
                    $inner_styles .= 'height:' . $data['height'] . 'px;';
                }
                if( !empty($data['padding']) ) {
                    $order = array('top', 'right', 'bottom', 'left');
                    $paddings = array();
                    $padding_values = explode(' ', $data['padding']);
                    foreach( $order as $k => $v) {
                        if( !isset($padding_values[$k]) ) {
                            $padding_values[$k] = '0px';
                        }else{
                            if( (!preg_match("/px/i", $padding_values[$k])) && (!preg_match("/%/i", $padding_values[$k])) ) {
                                $paddings[$k] = $padding_values[$k].'px';
                            }else{
                                $paddings[$k] = $padding_values[$k];
                            }
                        }
                    }
                    $inner_styles .= 'padding:' . implode(" ", $paddings) . ';';
                }
            }

            $result .= '<div class="super-element-inner' . $inner_class . '"' . ( !empty($inner_styles) ? ' style="' . $inner_styles . '"' : '' ) . '>';
                if( ( $tag!='column' ) && ( $tag!='multipart' ) ) {
                    if( empty($data) ) $data = null;
                    if( empty($inner) ) $inner = null;
                    $result .= self::output_element_html( $tag, $group, $data, $inner, $shortcodes, $settings );
                }
                if( !empty( $inner ) ) {
                    foreach( $inner as $k => $v ) {
                        if( empty($v['data'] ) ) $v['data'] = null;
                        if( empty($v['inner'] ) ) $v['inner'] = null;
                        $result .= self::output_builder_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings );
                    }
                }
            $result .= '</div>';

            $result .= '<textarea name="element-options">' . htmlentities( json_encode( $shortcodes[$group]['shortcodes'][$tag]['atts'] ), ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED ) . '</textarea>';
            $result .= '<textarea name="element-data">' . htmlentities( json_encode( $data ), ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED ) . '</textarea>';
        $result .= '</div>';
        
        return $result;
        
    }
    public static function error( $tag, $atts, $position ) {   
        if( !empty($atts['error']) ) {
            if( empty($atts['error']['position']) ) $atts['error']['position'] = 'after_field';
            if( $atts['error']['position']==$position ) {
                $html = '<div class="super-error">';
                $html .= $atts['error']['value'];
                $html .= '</div>';
                return $html;
            }
        }
    }
    public static function opening_tag( $tag, $atts, $class='', $styles='' ) {        
        
        $desc_style = '';
        if(is_array($atts['label'])){
            $class .= ' super-label-' . (isset($atts['label']['position']) ? $atts['label']['position'] : 'top') . '-' . (isset($atts['label']['alignment']) ? $atts['label']['alignment'] : 'left');
            $class .= ' super-position-' . (isset($atts['label']['position']) ? $atts['label']['position'] : 'top');
            $class .= ' super-align-' . (isset($atts['label']['alignment']) ? $atts['label']['alignment'] : 'left');

            if( isset($atts['label']['width']) ) {
                if($atts['label']['width']=='flex'){
                    if(!isset($atts['label']['flex_size'])) $atts['label']['flex_size'] = '1/2';
                    $sizes = array(
                        '1/5'=>'one_fifth',
                        '1/4'=>'one_fourth',
                        '1/3'=>'one_third',
                        '2/5'=>'two_fifth',
                        '1/2'=>'one_half',
                        '3/5'=>'three_fifth',
                        '2/3'=>'two_third',
                        '3/4'=>'three_fourth',
                        '4/5'=>'four_fifth',
                        '1/1'=>'one_full'
                    ); 
                    $class .= ' super_' . $sizes[$atts['label']['flex_size']];
                }
                if($atts['label']['width']=='fixed'){
                    $desc_style = 'width:'.$atts['label']['size'].$atts['label']['unit'].';';
                }
            }
        }

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

        // @since 3.2.0 - custom TAB index
        if( (isset($atts['custom_tab_index'])) && ($atts['custom_tab_index']>=0) ) {
            $result .= ' data-super-custom-tab-index="' . absint($atts['custom_tab_index']) . '"';   
        }

        $result .= '>';

        if( ($tag!='hidden') && ($tag!='recaptcha') ) {
            // Check if we need to display the Error message above the field container
            $result .= self::error( $tag, $atts, 'before_container' );
            
            // Open field container
            $result .= '<div class="super-field-container">';
        }

        // Compatibility with older super forms versions
        $label_data = self::backwards_compatibility_label_description($atts);

        // Only put the label and description wrapper above the field wrapper when positioned top or left
        if($label_data['position']=='top' || $label_data['position']=='left'){
            $result .= self::label_description($atts, $label_data['label'], $label_data['description'], $desc_style);
        }

        return $result;
    }

    public static function backwards_compatibility_label_description($atts){
        $position = 'top';
        if(is_array($atts['label'])){
            $label = $atts['label']['value'];
            $position = (isset($atts['label']['position']) ? $atts['label']['position'] : 'top');
        }else{
            $label = $atts['label'];
        }
        if(is_array($atts['description'])){
            $description = $atts['description']['value'];
        }else{
            $description = $atts['description'];
        }
        return array(
            'position' => $position,
            'label' => $label,
            'description' => $description
        );
    }

    public static function label_description($atts, $label, $description, $styles){
        $padding = (isset($atts['label']['padding']) ? $atts['label']['padding'] : '');
        if(!empty($padding)){
            $unit = (isset($atts['label']['padding']['unit']) ? $atts['label']['padding']['unit'] : 'px');
            $styles .= 'padding:'.$padding['top'].$unit.' '.$padding['right'].$unit.' '.$padding['bottom'].$unit.' '.$padding['left'].$unit.';';
        }

        $result = '';
        if( (!empty($label)) || (!empty($description)) ) {
            $result .= '<div class="super-label-description"' . (!empty($styles) ? ' style="' . $styles . '"' : '') . '>';
            
                // Check if we need to display the Error message above the field label/description
                $result .= self::error( $tag, $atts, 'before_label' );

                // Check if we need to display the label
                if( !empty($label) ) {
                    if($label===' '){
                        $label = '&nbsp;';
                    }
                    $bottom_margin = false;
                    if( empty($description) ) {
                        $bottom_margin = true;
                    }
                    $result .= self::field_label( $label, $bottom_margin, $atts['label'] );
                }
                // Check if we need to display the description
                if( !empty($description) ) {
                    if($description===' '){
                        $description = '&nbsp;';
                    }
                    $result .= self::field_description( $description, $atts['description'] );
                }

                // Check if we need to display the Error message below the field label/description
                $result .= self::error( $tag, $atts, 'after_label' );

            $result .= '</div>';
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

    public static function get_font_styles($font){
        $styles = '';
        if( isset($font) ) {
            if( isset($font['color']) ) {
                $styles .= 'color:'.$font['color'].';';
            }
            if( isset($font['family']) ) {
                $styles .= 'font-family:'.$font['family'].';';
            }
            if( isset($font['size']) ) {
                if( !isset($font['unit']) ) {
                    $font['unit'] = 'px';
                }
                $styles .= 'font-size:'.$font['size'].$font['unit'].';';
            }
            if( isset($font['weight']) ) {
                $styles .= 'font-weight:'.$font['weight'].';';
            }
            if( isset($font['transform']) ) {
                if($font['transform']!='default') $styles .= 'text-transform:'.$font['transform'].';';
            }
            if( isset($font['style']) ) {
                if($font['style']!='default') $styles .= 'font-style:'.$font['style'].';';
            }
            if( isset($font['decoration']) ) {
                if($font['decoration']!='default') $styles .= 'text-decoration:'.$font['decoration'].';';
            }
            if( isset($font['line_height']) ) {
                if( !isset($font['lunit']) ) {
                    $font['lunit'] = 'px';
                }
                $styles .= 'line-height:'.$font['line_height'].$font['lunit'].';';
            }
            if( isset($font['spacing']) ) {
                if( !isset($font['lsunit']) ) {
                    $font['lsunit'] = 'px';
                }
                $styles .= 'letter-spacing:'.$font['spacing'].$font['lsunit'].';';
            }
        }
        return $styles;
    }
    public static function field_label( $label, $bottom_margin, $data ) {
        // Old super forms fallback
        if( is_array($label) ) $label = $label['value'];
        $class = '';
        if( $bottom_margin==true ) $class = ' super-bottom-margin';
        if(isset($data['font'])) $styles = self::get_font_styles($data['font']);
        return '<div class="super-label' . $class . '"' . (!empty($styles) ? ' style="'.$styles.'"' : '') . '>' . stripslashes($label) . '</div>';
    }
    public static function field_description( $description, $data ) {        
        // Old super forms fallback
        if( is_array($description) ) $description = $description['value'];
        if(isset($data['font'])) $styles = self::get_font_styles($data['font']);
        return '<div class="super-description"' . (!empty($styles) ? ' style="'.$styles.'"' : '') . '>' . stripslashes($description) . '</div>';
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


        $result = '<div class="super-field-wrapper-container">';

        // Check if we need to display the Error message above the field wrapper
        $result .= self::error( $tag, $atts, 'before_field' );

        $result .= '<div' . $style . ' class="super-field-wrapper' . ($atts['icon']!='' ? ' super-icon-' . $atts['icon_position'] . ' super-icon-' . $atts['icon_align'] : '') . '">';

        if($atts['icon']!=''){
            $result .= '<i class="fa fa-'.$atts['icon'].' super-icon"></i>';
        }
        return $result;
    }
    public static function element_footer( $tag, $atts=array() ) {
        // Close field wrapper
        $result = '</div>';
        // Check if we need to display the Error message below the field wrapper
        $result .= self::error( $tag, $atts, 'after_field' );
        // Close field wrapper container
        $result .= '</div>';

        if($tag=='quantity') {
            $result .= '<span class="super-plus-button super-noselect"><i>+</i></span>';
        }
        if($tag=='toggle') {
            if( !isset($atts['suffix_label']) ) $atts['suffix_label'] = '';
            if( !isset($atts['suffix_tooltip']) ) $atts['suffix_tooltip'] = '';
            if( ($atts['suffix_label']!='') || ($atts['suffix_tooltip']!='') ) {
                $result .= '<div class="super-toggle-suffix-label">';
                if($atts['suffix_label']!='') $result .= $atts['suffix_label'];
                if($atts['suffix_tooltip']!='') $result .= '<span class="super-toggle-suffix-question super-tooltip" title="' . esc_attr( stripslashes( $atts['suffix_tooltip'] ) ) . '"></span>';
                $result .= '</div>';
            }
        }
        if($tag=='color') {
            if( !isset( $atts['suffix_label'] ) ) $atts['suffix_label'] = '';
            if( !isset( $atts['suffix_tooltip'] ) ) $atts['suffix_tooltip'] = '';
            if( ($atts['suffix_label']!='') || ($atts['suffix_tooltip']!='') ) {
                $result .= '<div class="super-toggle-suffix-label">';
                if($atts['suffix_label']!='') $result .= $atts['suffix_label'];
                if($atts['suffix_tooltip']!='') $result .= '<span class="super-toggle-suffix-question super-tooltip" title="' . esc_attr( stripslashes( $atts['suffix_tooltip'] ) ) . '"></span>';
                $result .= '</div>';
            }
        }

        $result .= self::loop_conditions( $atts );
        // Compatibility with older super forms versions
        $label_data = self::backwards_compatibility_label_description($atts);
        // Only put the label and description wrapper above the field wrapper when positioned top or left
        if($label_data['position']=='bottom' || $label_data['position']=='right'){
            $result .= self::label_description($atts, $label_data['label'], $label_data['description']);
        }
        // Close field container
        $result .= '</div>';
        // Check if we need to display the Error message below the field container
        $result .= self::error( $tag, $atts, 'after_container' );
        $result .= '</div>';
        return $result;
    }
    public static function common_attributes( $atts, $tag ) {        
        
        if( !isset( $atts['error'] ) ) $atts['error'] = '';
        if( !isset( $atts['validation'] ) ) $atts['validation'] = '';
        if( !isset( $atts['conditional_validation'] ) ) $atts['conditional_validation'] = '';
        if( !isset( $atts['conditional_validation_value'] ) ) $atts['conditional_validation_value'] = '';
        if( !isset( $atts['conditional_validation_value2'] ) ) $atts['conditional_validation_value2'] = ''; // @since 3.6.0
        if( !isset( $atts['may_be_empty'] ) ) $atts['may_be_empty'] = 'false';
        if( !isset( $atts['email'] ) ) $atts['email'] = '';
        if( !isset( $atts['exclude'] ) ) $atts['exclude'] = 0;
        if( !isset( $atts['replace_commas'] ) ) $atts['replace_commas'] = '';
        if( !isset( $atts['exclude_entry'] ) ) $atts['exclude_entry'] = '';
        if( !isset( $atts['maxlength'] ) ) $atts['maxlength'] = 0;
        if( !isset( $atts['minlength'] ) ) $atts['minlength'] = 0;

        // @since 2.6.0 - IBAN validation
        if( $atts['validation']=='iban' ) {
            wp_enqueue_script( 'super-iban-check', SUPER_PLUGIN_FILE . 'assets/js/frontend/iban-check.min.js', array(), SUPER_VERSION );
        }

        $data_attributes = array(
            'message' => $atts['error'],
            'validation' => $atts['validation'],
            'may-be-empty' => $atts['may_be_empty'],
            'conditional-validation' => $atts['conditional_validation'],
            'conditional-validation-value' => $atts['conditional_validation_value'],
            'conditional-validation-value2' => $atts['conditional_validation_value2'], // @since 3.6.0
            'email' => $atts['email'],
            'exclude' => $atts['exclude'],
            'replace-commas' => $atts['replace_commas'],
            'exclude-entry' => $atts['exclude_entry']
        );
        if( $atts['validation']=='none' ) unset($data_attributes['validation']);
        if( $atts['may_be_empty']=='false' ) unset($data_attributes['may-be-empty']);
        if( $atts['conditional_validation']=='none' ) unset($data_attributes['conditional-validation']);

        $result = '';
        foreach( $data_attributes as $k => $v ) {
            if( $v!='' ) {
                $result .= ' data-' . $k . '="' . $v . '"';
            }
        }
        
        // @since 2.0.0 - default value data attribute needed for Clear button
        if( isset($atts['value']) ) $result .= ' data-default-value="' . esc_attr($atts['value']) . '"';

        // @since 1.2.2
        if( !empty( $atts['disabled'] ) ) $result .= ' disabled="' . $atts['disabled'] . '"';

        // @since 3.6.0
        if( !empty( $atts['readonly'] ) ) $result .= ' readonly="true"';

        // @since 3.6.0 - disable field autocompletion
        if( !empty($atts['autocomplete']) ) $result .= ' autocomplete="off"';

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
            if( $atts['admin_email_value']!='value' ) {
                $result .= ' data-admin-email-value="' . $atts['admin_email_value'] . '"';
            }
            if( $atts['confirm_email_value']!='value' ) {
                $result .= ' data-confirm-email-value="' . $atts['confirm_email_value'] . '"';
            }

            // @since 1.2.9
            if( $atts['contact_entry_value']!='value' ) {
                $result .= ' data-contact-entry-value="' . $atts['contact_entry_value'] . '"';
            }

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
                    if( !empty($atts['mask']) ) {
                        wp_enqueue_script( 'super-masked-input', SUPER_PLUGIN_FILE . 'assets/js/frontend/masked-input.min.js', array(), SUPER_VERSION );
                        $result .= ' data-mask="' . esc_attr($atts['mask']) . '"';
                    }
                    if( $atts['maxlength']>0 ) {
                        $result .= ' maxlength="' . $atts['maxlength'] . '"';
                    }
                }
                if( $atts['maxlength']>0 ) {
                    $result .= ' data-maxlength="' . $atts['maxlength'] . '"';
                }
                if( $atts['minlength']>0 ) {
                    $result .= ' data-minlength="' . $atts['minlength'] . '"';
                }
            }

            if( (isset($atts['maxnumber'])) && ($atts['maxnumber']>0) ) {
                $result .= ' data-maxnumber="' . $atts['maxnumber'] . '"';
            }
            if( (isset($atts['minnumber'])) && ($atts['minnumber']>0) ) {
                $result .= ' data-minnumber="' . $atts['minnumber'] . '"';
            }
        }

        // @since 1.2.7     - super_common_attributes_filter
        return apply_filters( 'super_common_attributes_filter', $result, array( 'atts'=>$atts, 'tag'=>$tag ) );

    }

    // @since 1.2.5     - custom regex validation
    public static function custom_regex( $regex ) {
        if( !empty($regex) )return '<textarea disabled class="super-custom-regex">' . $regex . '</textarea>';
    }

    public static function loop_conditions( $atts ) {
        if( !isset( $atts['conditional_action'] ) ) $atts['conditional_action'] = 'disabled';
        if( !isset( $atts['conditional_items'] ) ) $atts['conditional_items'] = '';
        if( ( $atts['conditional_items']!=null ) && ( $atts['conditional_action']!='disabled' ) ) {
            
            // @since 4.2.0 - filter hook to change conditional items on the fly for specific element
            if( !empty($atts['name']) ) {
                $atts['conditional_items'] = apply_filters( 'super_conditional_items_' . $atts['name'] . '_filter', $atts['conditional_items'], array( 'atts'=>$atts ) );
            }

            // @since 2.3.0 - speed improvement for conditional logics
            // append the field names ad attribute that the conditions being applied to, so we can filter on it on field change with javascript
            $fields = array();
            $tags = array();
            foreach( $atts['conditional_items'] as $k => $v ) {
                if( !isset( $v['logic'] ) ) $v['logic'] = '';
                if( !isset( $v['logic_and'] ) ) $v['logic_and'] = '';
                if( $v['logic']!='' ) $fields[$v['field']] = $v['field'];
                if( $v['logic_and']!='' ) $fields[$v['field_and']] = $v['field_and'];

                // @since 3.5.0 - also check if variable field contains tags and if so, update the correct values
                if( $v['value']!='' ) {
                    preg_match_all('/{\K[^}]*(?=})/m', $v['value'], $matches);
                    $tags = array_unique(array_merge($tags, $matches[0]), SORT_REGULAR);
                }
                if( (!empty($v['and_method'])) && ( ($v['and_method']!='') && ($v['value_and']!='') ) ) {
                    preg_match_all('/{\K[^}]*(?=})/m', $v['value_and'], $matches);
                    $tags = array_unique(array_merge($tags, $matches[0]), SORT_REGULAR);
                }

            }
            $fields = implode('][', $fields);
            $tags = implode('][', $tags);

            // @since 1.7 - use json instead of HTML for speed improvements
            return '<textarea class="super-conditional-logic" data-fields="[' . $fields . ']" data-tags="[' . $tags . ']">' . json_encode($atts['conditional_items']) . '</textarea>';
        }
    }
    
    // @since 1.2.7    - variable conditions
    public static function loop_variable_conditions( $atts ) {
        if( !isset( $atts['conditional_variable_action'] ) ) $atts['conditional_variable_action'] = 'disabled';
        if( $atts['conditional_variable_action']!='disabled' ) {
            if( !isset( $atts['conditional_items'] ) ) $atts['conditional_items'] = '';
            
            // @since 4.2.0 - variable conditions based on CSV file
            if( (!empty($atts['conditional_variable_method'])) && ($atts['conditional_variable_method']=='csv') ) {
                $delimiter = ',';
                $enclosure = '"';
                if( !empty( $atts['conditional_variable_delimiter'] ) ) $delimiter = $atts['conditional_variable_delimiter'];
                if( !empty( $atts['conditional_variable_enclosure'] ) ) $enclosure = stripslashes($atts['conditional_variable_enclosure']);
                $file = get_attached_file($atts['conditional_variable_csv']);
                $rows = array();
                $conditions = array();
                if( $file ) {
                    $row = 0;
                    if (($handle = fopen($file, "r")) !== FALSE) {
                        while (($data = fgetcsv($handle, 10000, $delimiter, $enclosure)) !== FALSE) {
                            $rows[] = $data;
                        }
                        fclose($handle);
                        $columns = $rows[0];
                        foreach( $rows as $k => $v ) {
                            if( $k==0 ) continue;
                            foreach( $v as $kk => $vv ) {
                                if( $kk==0 ) continue;
                                $conditions[] = array(
                                    'field' => $atts['conditional_variable_row'],
                                    'logic' => $atts['conditional_variable_logic'], //'greater_than_or_equal',
                                    'value' => $v[0],
                                    'and_method' => $atts['conditional_variable_and_method'], //'and',
                                    'field_and' => $atts['conditional_variable_col'],
                                    'logic_and' => $atts['conditional_variable_logic_and'], //'greater_than_or_equal',
                                    'value_and' => $columns[$kk],
                                    'new_value' => $vv
                                );
                            }
                        }
                        
                    }
                }
                $atts['conditional_items'] = $conditions;
            }

            if( $atts['conditional_items']!=null ) {
                
                // @since 4.2.0 - filter hook to change variable conditions on the fly for specific field
                if( !empty($atts['name']) ) {
                    $atts['conditional_items'] = apply_filters( 'super_variable_conditions_' . $atts['name'] . '_filter', $atts['conditional_items'], array( 'atts'=>$atts ) );
                }

                // @since 2.3.0 - speed improvement for variable field
                // append the field names ad attribute that the conditions being applied to, so we can filter on it on field change with javascript
                $fields = array();
                $tags = array();
                foreach( $atts['conditional_items'] as $k => $v ) {
                    if( !isset( $v['logic'] ) ) $v['logic'] = '';
                    if( !isset( $v['logic_and'] ) ) $v['logic_and'] = '';                
                    if( $v['logic']!='' ) $fields[$v['field']] = $v['field'];
                    if( $v['logic_and']!='' ) $fields[$v['field_and']] = $v['field_and'];

                    // @since 4.2.0 - als check for {tags} in "value" and "AND value"
                    if( $v['value']!='' ) {
                        preg_match_all('/{\K[^}]*(?=})/m', $v['value'], $matches);
                        $tags = array_unique(array_merge($tags, $matches[0]), SORT_REGULAR);
                    }
                    if( (!empty($v['and_method'])) && ( ($v['and_method']!='') && ($v['value_and']!='') ) ) {
                        preg_match_all('/{\K[^}]*(?=})/m', $v['value_and'], $matches);
                        $tags = array_unique(array_merge($tags, $matches[0]), SORT_REGULAR);
                    }

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
            }
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
      
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'layout_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        // @since 2.6.0 - add active class to the first multipart element
        if( !isset($GLOBALS['super_first_multipart']) ) {
            $GLOBALS['super_first_multipart'] = true;
            $atts['class'] = 'active '.$atts['class']; 
        }

        $result  = '';
        $result .= '<div class="super-shortcode super-' . $tag . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" ' . ($atts['validate']=='true' ? ' data-validate="' . $atts['validate'] . '"' : '') . 'data-step-auto="' . $atts['auto'] .'" data-step-name="' . $atts['step_name'] .'" data-step-description="' . $atts['step_description'] . '"';
        
        // @since 4.2.0 - disable scrolling when multi-part contains errors
        if( !empty($atts['disable_scroll']) ) $result .= ' data-disable-scroll="true"';

        // @since 4.3.0 - disable scrolling for multi-part next prev
        if( !empty($atts['disable_scroll_pn']) ) $result .= ' data-disable-scroll-pn="true"';

        // @since 1.2.5
        if( isset( $atts['prev_text'] ) ) $result .= ' data-prev-text="' . $atts['prev_text'] . '"';
        if( isset( $atts['next_text'] ) ) $result .= ' data-next-text="' . $atts['next_text'] . '"';
        
        // @since 3.6.0 - disable autofocus first field
        if( !empty( $atts['autofocus'] ) ) $result .= ' data-disable-autofocus="true"';
        
        $result .= ' data-icon="' . $atts['icon'] . '">';
        if( !empty( $inner ) ) {
            // Before doing the actuall loop we need to know how many columns this form contains
            // This way we can make sure to correctly close the column system
            $GLOBALS['super_column_found'] = 0;
            foreach( $inner as $k => $v ) {
                if( $v['tag']=='column' ) $GLOBALS['super_column_found']++;
            }
            foreach( $inner as $k => $v ) {
                if( empty($v['data']) ) $v['data'] = null;
                if( empty($v['inner']) ) $v['inner'] = null;
                $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings, $entry_data );
            }
        }
        unset($GLOBALS['super_grid_system']);
        $result .= '</div>';
        return $result;
    }
    public static function column( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'layout_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
       
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


        if( !empty($atts['radius']) ) {
            $order = array('top_left', 'top_right', 'bottom_right', 'bottom_left');
            $radius = array();
            $radius_values = explode(' ', $atts['radius']);
            foreach( $order as $k => $v) {
                if( !isset($radius_values[$k]) ) {
                    $radius_values[$k] = '0px';
                }else{
                    if( (!preg_match("/px/i", $radius_values[$k])) && (!preg_match("/%/i", $radius_values[$k])) ) {
                        $radius[$k] = $radius_values[$k].'px';
                    }else{
                        $radius[$k] = $radius_values[$k];
                    }
                }
            }

            $identical = count(array_unique($radius))==1 ? true : false;
            $final_radius_css = '';
            if($identical){
                if($radius[0]!=0){
                    $final_radius_css .= '-webkit-border-radius: '. $radius[0] . ';';
                    $final_radius_css .= '-moz-border-radius: '. $radius[0] . ';';
                    $final_radius_css .= 'border-radius: '. $radius[0] . ';';
                }
            }else{
                $final_radius_css .= '-webkit-border-top-left-radius: '. $radius[0] . ';';
                $final_radius_css .= '-moz-border-radius-topleft: '. $radius[0] . ';';
                $final_radius_css .= 'border-top-left-radius: '. $radius[0] . ';';

                $final_radius_css .= '-webkit-border-top-right-radius: '. $radius[1] . ';';
                $final_radius_css .= '-moz-border-radius-topright: '. $radius[1] . ';';
                $final_radius_css .= 'border-top-right-radius: '. $radius[1] . ';';
                
                $final_radius_css .= '-webkit-border-bottom-right-radius: '. $radius[2] . ';';
                $final_radius_css .= '-moz-border-radius-bottomright: '. $radius[2] . ';';
                $final_radius_css .= 'border-bottom-right-radius: '. $radius[2] . ';';

                $final_radius_css .= '-webkit-border-bottom-left-radius: '. $radius[3] . ';';
                $final_radius_css .= '-moz-border-radius-bottomleft: '. $radius[3] . ';';
                $final_radius_css .= 'border-bottom-left-radius: '. $radius[3] . ';';
            }
            if($final_radius_css!=''){
                $styles .= $final_radius_css;
            }
        }
        if( !empty($atts['margin']) ) {
            $order = array('top', 'right', 'bottom', 'left');
            $margins = array();
            $margin_values = explode(' ', $atts['margin']);
            foreach( $order as $k => $v) {
                if( !isset($margin_values[$k]) ) {
                    $margin_values[$k] = '0px';
                }else{
                    if( (!preg_match("/px/i", $margin_values[$k])) && (!preg_match("/%/i", $margin_values[$k])) ) {
                        $margins[$k] = $margin_values[$k].'px';
                    }else{
                        $margins[$k] = $margin_values[$k];
                    }
                }
            }
            $styles .= 'margin:' . implode(" ", $margins) . ';';
        }
        if( !empty($atts['padding']) ) {
            $order = array('top', 'right', 'bottom', 'left');
            $paddings = array();
            $padding_values = explode(' ', $atts['padding']);
            foreach( $order as $k => $v) {
                if( !isset($padding_values[$k]) ) {
                    $padding_values[$k] = '0px';
                }else{
                    if( (!preg_match("/px/i", $padding_values[$k])) && (!preg_match("/%/i", $padding_values[$k])) ) {
                        $paddings[$k] = $padding_values[$k].'px';
                    }else{
                        $paddings[$k] = $padding_values[$k];
                    }
                }
            }
            $styles .= 'padding:' . implode(" ", $paddings) . ';';
        }

        // @since   1.9 - background image
        if( !isset( $atts['bg_image'] ) ) $atts['bg_image'] = '';
        if( $atts['bg_image']!='' ) {
            $image = wp_get_attachment_image_src( $atts['bg_image'], 'original' );
            $image = !empty( $image[0] ) ? $image[0] : '';
            $image = wp_make_link_relative( $image );
            if( !empty( $image ) ) {
                $styles .= 'background-image: url(' . $image . ');';
            }
        }

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

        if(!isset($atts['width'])) $atts['width'] = 100;
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
            'custom'=>array('custom_size',$atts['width'])
        );
        if(empty($atts['size'])) $atts['size'] = '1/1';
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

        if( $atts['size']=='custom' ) {
            $styles .= 'width:' . $atts['width'] . '%;';
            if( !empty($atts['height']) ) {
                $styles .= 'height:' . $atts['height'] . 'px;';
            }
        }
        if( $styles!='' ) $styles = ' style="' . $styles . '"';

        $result .= '<div class="super-shortcode super_' . $sizes[$atts['size']][0] . ' super-column'.$atts['invisible'].' column-number-'.$grid['columns'][$grid['level']]['current'].' grid-level-'.$grid['level'].' ' . $class . ($atts['resize_disabled_mobile']==true ? ' super-not-responsive' : '') . ($atts['resize_disabled_mobile_window']==true ? ' super-not-responsive-window' : '') . ($atts['hide_on_mobile']==true ? ' super-hide-mobile' : '') . ($atts['hide_on_mobile_window']==true ? ' super-hide-mobile-window' : '') . ($atts['force_responsiveness_mobile_window']==true ? ' super-force-responsiveness-window' : '') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"' . $styles; 
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
            $grid['level']++;
            if( $atts['duplicate']=='enabled' ) {
                $result .= '<div class="super-shortcode super-duplicate-column-fields">';
            }
            $GLOBALS['super_grid_system'] = $grid;
            $GLOBALS['super_column_found'] = 0;
            foreach( $inner as $k => $v ) {
                if( $v['tag']=='column' ) $GLOBALS['super_column_found']++;
            }
            foreach( $inner as $k => $v ) {
                if( empty($v['data']) ) $v['data'] = null;
                if( empty($v['inner']) ) $v['inner'] = null;
                $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings, $entry_data );
            }
            if( $atts['duplicate']=='enabled' ) {
                $result .= '<div class="super-duplicate-actions">';
                $result .= '<span class="super-add-duplicate"></span>';
                $result .= '<span class="super-delete-duplicate"></span>';
                $result .= '</div>';
                $result .= '</div>';
            }

            if( ($entry_data) && (isset($entry_data[$inner[0]['data']['name'].'_2'])) ){
                if( $atts['duplicate']=='enabled' ) {
                    $result .= '<div class="super-shortcode super-duplicate-column-fields">';
                }
                $GLOBALS['super_grid_system'] = $grid;
                $GLOBALS['super_column_found'] = 0;
                foreach( $inner as $k => $v ) {
                    if( $v['tag']=='column' ) $GLOBALS['super_column_found']++;
                }
                $i = 2;
                foreach( $inner as $k => $v ) {
                    if( empty($v['data']) ) $v['data'] = null;
                    if( empty($v['inner']) ) $v['inner'] = null;
                    if(isset($v['data']['name'])){
                        $name = $v['data']['name'].'_'.$i;
                        if(isset($entry_data[$name])){
                            $v['data']['name'] = $name;
                            $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings, $entry_data );
                        }
                    }
                }
                if( $atts['duplicate']=='enabled' ) {
                    $result .= '<div class="super-duplicate-actions">';
                    $result .= '<span class="super-add-duplicate"></span>';
                    $result .= '<span class="super-delete-duplicate"></span>';
                    $result .= '</div>';
                    $result .= '</div>';
                }
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
            $result .= '<div style="clear:both;"></div>';
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

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        if( empty($atts['wrapper_width']) ) $atts['wrapper_width'] = 50;
        if( empty($settings['theme_field_size']) ) $settings['theme_field_size'] = 'medium';
        if( $settings['theme_field_size']=='large' ) $atts['wrapper_width'] = $atts['wrapper_width']+20;
        if( $settings['theme_field_size']=='huge' ) $atts['wrapper_width'] = $atts['wrapper_width']+30;

        $result = self::opening_tag( $tag, $atts );
        $result .= '<span class="super-minus-button super-noselect"><i>-</i></span>';
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<input class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="text"';

        // @since   1.1.8 - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = sanitize_text_field( $_POST[$atts['name']] );
        }

        // @since   4.2.0 - autopopulate with last entry data
        if( isset( $entry_data[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $entry_data[$atts['name']]['value'] );
        }

        if( empty($atts['value']) ) $atts['value'] = '0';
        if( empty($atts['minnumber']) ) $atts['minnumber'] = 0;
        if( empty( $atts['maxnumber']) ) $atts['maxnumber'] = 100;

        if( $atts['value']<$atts['minnumber'] ) {
            $atts['value'] = $atts['minnumber'];
        }
        if( $atts['value']>$atts['maxnumber'] ) {
            $atts['value'] = $atts['maxnumber'];
        }

        $result .= ' name="' . $atts['name'] . '" value="' . $atts['value'] . '" data-steps="' . $atts['steps'] . '" data-minnumber="' . $atts['minnumber'] . '" data-maxnumber="' . $atts['maxnumber'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= self::element_footer($tag, $atts);
        return $result;

    }


    /** 
     *  Toggle field
     *
     *  @since      2.9.0
    */    
    public static function toggle_field( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        $atts['validation'] = 'empty';
        if( (!isset($atts['wrapper_width'])) || ($atts['wrapper_width']==0) ) $atts['wrapper_width'] = 70;
        if( ($settings['theme_hide_icons']=='no') && ($atts['icon']!='') ) {
            if( !isset($settings['theme_field_size']) ) $settings['theme_field_size'] = 'medium';
            $atts['wrapper_width'] = $atts['wrapper_width']+33;
            if($settings['theme_field_size']=='large') $atts['wrapper_width'] = $atts['wrapper_width']+20;
            if($settings['theme_field_size']=='huge') $atts['wrapper_width'] = $atts['wrapper_width']+40;
        }
        $result = self::opening_tag( $tag, $atts );
        
        if(!isset($atts['prefix_label'])) $atts['prefix_label'] = '';
        if(!isset($atts['prefix_tooltip'])) $atts['prefix_tooltip'] = '';
        if( ($atts['prefix_label']!='') || ($atts['prefix_tooltip']!='') ) {
            $result .= '<div class="super-toggle-prefix-label">';
            if($atts['prefix_label']!='') $result .= $atts['prefix_label'];
            if($atts['prefix_tooltip']!='') $result .= '<span class="super-toggle-prefix-question super-tooltip" title="' . esc_attr( stripslashes( $atts['prefix_tooltip'] ) ) . '"></span>';
            $result .= '</div>';
        }

        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since   1.1.8 - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = sanitize_text_field( $_POST[$atts['name']] );
        }

        // @since 3.5.0 - add shortcode compatibility for default field value
        $atts['value'] = do_shortcode($atts['value']); 

        // @since   2.9.0 - autopopulate with last entry data
        if( isset( $entry_data[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $entry_data[$atts['name']]['value'] );
        }

        if( ( !isset( $atts['value'] ) ) || ( $atts['value']=='' ) ) $atts['value'] = '0';

        $result .= '<div class="super-toggle-switch ' . ( $atts['value']==1 ? 'super-active' : '' ) . '">';
            $result .= '<div class="super-toggle-group">';
                $result .= '<label class="super-toggle-on" data-value="' . $atts['on_value'] . '">' . $atts['on_label'] . '</label>';
                $result .= '<label class="super-toggle-off" data-value="' . $atts['off_value'] . '">' . $atts['off_label'] . '</label>';
                $result .= '<span class="super-toggle-handle"></span>';
            $result .= '</div>';
        $result .= '</div>';

        if( !isset($atts['class']) ) $atts['class'] = '';
        $result .= '<input class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="hidden"';
        $result .= ' name="' . $atts['name'] . '" value="' . ( $atts['value']==1 ? $atts['on_value'] : $atts['off_value'] ) . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';  

        $result .= self::element_footer($tag, $atts);
        return $result;
    }


    /** 
     *  Color picker
     *
     *  @since      3.1.0
    */    
    public static function color( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        wp_enqueue_style('super-colorpicker', SUPER_PLUGIN_FILE.'assets/css/frontend/colorpicker.min.css', array(), SUPER_VERSION);    
        wp_enqueue_script( 'super-colorpicker', SUPER_PLUGIN_FILE . 'assets/js/frontend/colorpicker.min.js' );

        if( (!isset($atts['wrapper_width'])) || ($atts['wrapper_width']==0) ) $atts['wrapper_width'] = 70;
        if( ($settings['theme_hide_icons']=='no') && ($atts['icon']!='') ) {
            if( !isset($settings['theme_field_size']) ) $settings['theme_field_size'] = 'medium';
            $atts['wrapper_width'] = $atts['wrapper_width']+33;
            if($settings['theme_field_size']=='large') $atts['wrapper_width'] = $atts['wrapper_width']+20;
            if($settings['theme_field_size']=='huge') $atts['wrapper_width'] = $atts['wrapper_width']+40;
        }
        
        $result = self::opening_tag( $tag, $atts );

        if( !isset($atts['prefix_label']) ) $atts['prefix_label'] = '';
        if( !isset($atts['prefix_tooltip']) ) $atts['prefix_tooltip'] = '';
        if( ($atts['prefix_label']!='') || ($atts['prefix_tooltip']!='') ) {
            $result .= '<div class="super-toggle-prefix-label">';
            if($atts['prefix_label']!='') $result .= $atts['prefix_label'];
            if($atts['prefix_tooltip']!='') $result .= '<span class="super-toggle-prefix-question super-tooltip" title="' . esc_attr( stripslashes( $atts['prefix_tooltip'] ) ) . '"></span>';
            $result .= '</div>';
        }

        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<input class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="text"';

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
        if($atts['value']!='') $atts['value'] = SUPER_Common::email_tags( $atts['value'], null, $settings );

        // @since 3.5.0 - add shortcode compatibility for default field value
        $atts['value'] = do_shortcode($atts['value']); 

        $result .= ' name="' . $atts['name'] . '" value="' . $atts['value'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        $result .= self::element_footer($tag, $atts);
        return $result;

    }


    /** 
     *  Slider field
     *
     *  @since      1.2.1
    */    
    public static function slider_field( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        wp_enqueue_style('super-simpleslider', SUPER_PLUGIN_FILE.'assets/css/backend/simpleslider.min.css', array(), SUPER_VERSION);    
        wp_enqueue_script('super-simpleslider', SUPER_PLUGIN_FILE.'assets/js/backend/simpleslider.min.js', array(), SUPER_VERSION); 
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        $result .= '<input class="super-shortcode-field" type="text"';
        
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

        $result .= ' name="' . $atts['name'] . '" value="' . $atts['value'] . '" data-decimals="' . $atts['decimals'] . '" data-thousand-separator="' . $atts['thousand_separator'] . '" data-decimal-separator="' . $atts['decimal_separator'] . '" data-steps="' . $atts['steps'] . '" data-currency="' . $atts['currency'] . '" data-format="' . $atts['format'] . '" data-minnumber="' . $atts['minnumber'] . '" data-maxnumber="' . $atts['maxnumber'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= self::element_footer($tag, $atts);
        return $result;
    }

    /** 
     *  Currency field
     *
     *  @since      2.1.0
    */ 
    public static function currency( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );       

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
        if($atts['value']!='') $atts['value'] = SUPER_Common::email_tags( $atts['value'], null, $settings );

        $result .= ' name="' . $atts['name'] . '" value="' . $atts['value'] . '" data-decimals="' . $atts['decimals'] . '" data-thousand-separator="' . $atts['thousand_separator'] . '" data-decimal-separator="' . $atts['decimal_separator'] . '" data-currency="' . $atts['currency'] . '" data-format="' . $atts['format'] . '"';

        // @since 4.2.0 - custom threshold to trigger hooks
        if( !empty($atts['threshold']) ) {
            $result .= ' data-threshold="' . $atts['threshold'] . '"';
        }
           
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        $result .= '<input type="hidden" value="' . str_replace($atts['thousand_separator'], "", $atts['value']) . '" />';

        
        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= self::element_footer($tag, $atts);
        return $result;
    }

    public static function text( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
      
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        // @since 3.1.0 - google distance calculation between 2 addresses
        $data_attributes = '';
        $distance_calculator_class = '';
        if( !isset( $atts['enable_distance_calculator'] ) ) $atts['enable_distance_calculator'] = '';
        if( $atts['enable_distance_calculator']=='true' ) {
            if( !isset( $atts['distance_method'] ) ) $atts['distance_method'] = 'start';
            if( !isset( $atts['distance_value'] ) ) $atts['distance_value'] = 'distance';
            if( !isset( $atts['distance_units'] ) ) $atts['distance_units'] = 'metric';
            if( !isset( $atts['distance_field'] ) ) $atts['distance_field'] = '';
            $data_attributes .= ' data-distance-method="'.$atts['distance_method'].'"';
            $data_attributes .= ' data-distance-value="'.$atts['distance_value'].'"';
            $data_attributes .= ' data-distance-units="'.$atts['distance_units'].'"';
            $data_attributes .= ' data-distance-field="'.$atts['distance_field'].'"';
            if( $atts['distance_method']=='start' ) {
                $data_attributes .= ' data-distance-destination="'.$atts['distance_destination'].'"';
            }else{
                $data_attributes .= ' data-distance-start="'.$atts['distance_start'].'"';
            }
            $distance_calculator_class .= ' super-distance-calculator';
        }

        // @since 3.0.0 - google places autocomplete/fill address based on user input
        $address_auto_populate_mappings = '';
        $address_auto_populate_class = '';
        if( !isset( $atts['enable_address_auto_complete'] ) ) $atts['enable_address_auto_complete'] = '';
        if( $atts['enable_address_auto_complete']=='true' ) {
            $address_auto_populate_class = ' super-address-autopopulate';
            // Check if we need to auto populate fields with the retrieved data
            if( !isset( $atts['enable_address_auto_populate'] ) ) $atts['enable_address_auto_populate'] = '';
            if( $atts['enable_address_auto_populate']=='true' ) {
                //onFocus="geolocate()"
                foreach($atts['address_auto_populate_mappings'] as $k => $v){
                    if( $v['field']!='' ) $address_auto_populate_mappings .= ' data-map-' . $v['key'] . '="' . $v['field'] . '|' . $v['type'] . '"';
                }
            }
            if( !isset( $atts['address_api_key'] ) ) $atts['address_api_key'] = '';
            wp_enqueue_script( 'super-google-maps-api', 'https://maps.googleapis.com/maps/api/js?key=' . $atts['address_api_key'] . '&libraries=drawing,geometry,places,visualization&callback=SUPER.google_maps_init', array( 'super-common' ), SUPER_VERSION, false );
        }

        // @since   1.2.4 - auto suggest feature
        if( !isset( $atts['enable_auto_suggest'] ) ) $atts['enable_auto_suggest'] = '';
        $class = ($atts['enable_auto_suggest']=='true' ? 'super-auto-suggest ' : '');

        // @since   3.7.0 - auto suggest wp tags
        if( empty($atts['keywords_retrieve_method']) ) $atts['keywords_retrieve_method'] = 'free';
        $class .= ($atts['keywords_retrieve_method']!='free' ? 'super-keyword-tags ' : '');

        // @since   3.1.0 - uppercase transformation
        if( !isset( $atts['uppercase'] ) ) $atts['uppercase'] = '';
        $class .= ($atts['uppercase']=='true' ? ' super-uppercase ' : '');

        $result = self::opening_tag( $tag, $atts, $class );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        
        // @since 2.9.0 - keyword enabled
        if( !isset( $atts['enable_keywords'] ) ) $atts['enable_keywords'] = '';
        if( !isset( $atts['keyword_split_method'] ) ) $atts['keyword_split_method'] = 'both';
        if( !isset( $atts['keyword_max'] ) ) $atts['keyword_max'] = 5;

        $result .= '<input class="super-shortcode-field';
        $result .= $distance_calculator_class;
        $result .= $address_auto_populate_class; 
        if( !empty($atts['class']) ) {
            $result .= ' ' . $atts['class'];
        }
        if( $atts['enable_keywords']=='true' ) {
            $result .= ' super-keyword';
        }
        $result .= '" type="text"';
        if( $atts['enable_keywords']=='true' ) {
            $result .= ' data-keyword-max="' . $atts['keyword_max'] . '" data-split-method="' . $atts['keyword_split_method'] . '"';
        }
        if( $atts['enable_distance_calculator']=='true' ) {
            $result .= $data_attributes;
        }

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
        $atts['value'] = esc_attr(stripslashes($atts['value']));

        if($atts['value']!='') $atts['value'] = SUPER_Common::email_tags( $atts['value'], null, $settings );

        // @since 3.5.0 - add shortcode compatibility for default field value
        $atts['value'] = do_shortcode($atts['value']); 

        if( $atts['enable_auto_suggest']=='true' ) {
            $items = array();
            if( !isset( $atts['retrieve_method'] ) ) $atts['retrieve_method'] = 'custom';
            if( $atts['retrieve_method']=='custom' ) {
                if( ( isset( $atts['autosuggest_items'] ) ) && ( count($atts['autosuggest_items'])!=0 ) && ( $atts['autosuggest_items']!='' ) ) {
                    foreach( $atts['autosuggest_items'] as $k => $v ) {
                        if( $v['checked']=='true' || $v['checked']==1 ) {
                            $atts['value'] = $v['value'];
                            $items[] = '<li data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '" class="selected super-default-selected">' . stripslashes($v['label']) . '</li>'; 
                        }else{
                            $items[] = '<li data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '">' . stripslashes($v['label']) . '</li>'; 
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

            // Retrieve product attributes
            if($atts['retrieve_method']=='product_attribute') {
                if( !isset( $atts['retrieve_method_product_attribute'] ) ) $atts['retrieve_method_product_attribute'] = '';
                if($atts['retrieve_method_product_attribute']!=''){
                    // Let's try to retrieve product attributes
                    if ( class_exists( 'WooCommerce' ) ) {
                        global $post;
                        if( isset( $post ) ) {
                            global $product;
                            $attributes = $product->get_attribute( $atts['retrieve_method_product_attribute'] );
                            $attributes = explode(', ', $attributes);
                            foreach( $attributes as $v ) {
                                $items[] = '<li data-value="' . esc_attr($v) . '" data-search-value="' . esc_attr( $v ) . '">' . $v . '</li>'; 
                            }
                        }
                    }          
                }
            }

            // @since   3.6.0
            if($atts['retrieve_method']=='tags') {
                $tags = get_tags(
                    array(
                        'hide_empty'=>false
                    )
                );
                foreach ( $tags as $v ) {
                    if( !isset( $atts['retrieve_method_value'] ) ) $atts['retrieve_method_value'] = 'slug';
                    if( $atts['retrieve_method_value']=='slug' ) {
                        $data_value = $v->slug;
                    }elseif( $atts['retrieve_method_value']=='id' ) {
                        $data_value = $v->term_id;
                    }else{
                        $data_value = $v->name;
                    }
                    $items[] = '<li data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr( $v->name ) . '">' . $v->name . '</li>'; 
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
                        while (($data = fgetcsv($handle, 10000, $delimiter, $enclosure)) !== FALSE) {
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

        $result .= ' name="' . $atts['name'] . '"';
        if( !empty($atts['value']) ) {
            $result .= ' value="' . $atts['value'] . '"';
        }

        // @since 2.2.0   - search / populate with contact entry data
        if( $atts['enable_search']=='true' ) {
            $result .= ' data-search="' . $atts['enable_search'] . '"';
            $result .= ' data-search-method="' . $atts['search_method'] . '"';
            
            // @since 3.2.0 - skip specific fields from being populated
            $skip = '';
            if( !empty($atts['search_skip']) ) {
                $skip = sanitize_text_field( $atts['search_skip'] );
                $result .= ' data-search-skip="' . $skip . '"';
            }

            // @since 3.1.0 - make sure if the parameter of this field element is set in the POST or GET we have to set the GET variables to auto fill the form fields based on the contact entry found
            if( $atts['value']!='' ) {
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

                // @since 3.2.0 - skip specific fields from being populated
                $skip_fields = explode( "|", $skip );
                foreach($skip_fields as $field_name){
                    if( isset($data[$field_name]) ) {
                        unset($data[$field_name]);
                    }
                }

                if( isset($entry[0])) {
                    $data['hidden_contact_entry_id'] = array(
                        'name' => 'hidden_contact_entry_id',
                        'value' => $entry[0]->ID,
                        'type' => 'entry_id'
                    );
                }
                if (is_array($data) || is_object($data)) {
                    foreach($data as $k => $v){
                        $_GET[$k] = $v['value'];
                    }
                }
            }
        }
        
        // @since 3.0.0 - add data attributes to map google places data to specific fields
        if( $address_auto_populate_mappings!='' ) $result .= $address_auto_populate_mappings;
        
        // @since 3.6.0 - disable autocomplete when either auto suggest or keyword functionality is enabled
        if( !empty($atts['enable_auto_suggest']) ) $atts['autocomplete'] = 'true';
        if( !empty($atts['enable_keywords']) ) $atts['autocomplete'] = 'true';

        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';
        
        // @since 2.9.0 - entered keywords
        if( !empty($atts['enable_keywords']) ) {

            if( empty($atts['keywords_retrieve_method']) ) $atts['keywords_retrieve_method'] = 'free';

            if( $atts['keywords_retrieve_method']=='free' ) {
                $result .= '<div class="super-entered-keywords">';
                $values = explode( ",", $atts['value'] );
                foreach( $values as $k => $v ) {
                    if($v!='') $result .= '<span>' . $v . '</span>';
                }
                $result .= '</div>';
            }else{
                $result .= '<div class="super-autosuggest-tags super-shortcode-field">';
                    $result .= '<div></div>';
                    $result .= '<input class="super-shortcode-field" type="text"';
                    if( !empty( $atts['placeholder'] ) ) {
                        $result .= ' placeholder="' . esc_attr($atts['placeholder']) . '" data-placeholder="' . esc_attr($atts['placeholder']) . '"';
                    }
                    $result .= ' />';
                $result .= '</div>';

                $items = array();
                
                if( $atts['keywords_retrieve_method']=='custom' ) {
                    if( ( isset( $atts['keywords_items'] ) ) && ( count($atts['keywords_items'])!=0 ) && ( $atts['keywords_items']!='' ) ) {
                        foreach( $atts['keywords_items'] as $k => $v ) {
                            if( $v['checked']=='true' || $v['checked']==1 ) {
                                $item = '<li class="super-active" data-value="' . esc_attr($v['value']) . '" data-search-value="' . esc_attr($v['label']) . '">';
                            }else{
                                $item = '<li data-value="' . esc_attr($v['value']) . '" data-search-value="' . esc_attr($v['label']) . '">';
                            }
                            $item .= '<span class="super-wp-tag">' . stripslashes($v['label']) . '</span>'; 
                            $item .= '</li>';
                            $items[] = $item;
                        }
                    }
                }      
                if($atts['keywords_retrieve_method']=='taxonomy') {
                    if( !isset( $atts['keywords_retrieve_method_taxonomy'] ) ) $atts['keywords_retrieve_method_taxonomy'] = 'category';
                    if( !isset( $atts['keywords_retrieve_method_exclude_taxonomy'] ) ) $atts['keywords_retrieve_method_exclude_taxonomy'] = '';
                    if( !isset( $atts['keywords_retrieve_method_hide_empty'] ) ) $atts['keywords_retrieve_method_hide_empty'] = 0;
                    if( !isset( $atts['keywords_retrieve_method_parent'] ) ) $atts['keywords_retrieve_method_parent'] = '';
                    
                    $args = array(
                        'hide_empty' => $atts['keywords_retrieve_method_hide_empty'],
                        'exclude' => $atts['keywords_retrieve_method_exclude_taxonomy'],
                        'taxonomy' => $atts['keywords_retrieve_method_taxonomy'],
                        'parent' => $atts['keywords_retrieve_method_parent'],
                    );
                    $categories = get_categories( $args );
                    foreach( $categories as $v ) {
                        
                        // @since 1.2.5
                        if( !isset( $atts['keywords_retrieve_method_value'] ) ) $atts['keywords_retrieve_method_value'] = 'slug';
                        if($atts['keywords_retrieve_method_value']=='slug'){
                            $data_value = $v->slug;
                        }elseif($atts['keywords_retrieve_method_value']=='id'){
                            $data_value = $v->ID;
                        }else{
                            $data_value = $v->name;
                        }
                        $item = '<li data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr($v->name) . '">';
                        $item .= '<span class="super-wp-tag">' . $v->name . '</span>'; 
                        $item .= '</li>';
                        $items[] = $item;
                    }
                }
                // @since   1.2.4
                if($atts['keywords_retrieve_method']=='post_type') {
                    if( !isset( $atts['keywords_retrieve_method_post'] ) ) $atts['keywords_retrieve_method_post'] = 'post';
                    if( !isset( $atts['keywords_retrieve_method_exclude_post'] ) ) $atts['keywords_retrieve_method_exclude_post'] = '';
                    if( !isset( $atts['keywords_retrieve_method_parent'] ) ) $atts['keywords_retrieve_method_parent'] = '';
                    $args = array(
                        'post_type' => $atts['keywords_retrieve_method_post'],
                        'exclude' => $atts['keywords_retrieve_method_exclude_post'],
                        'post_parent' => $atts['keywords_retrieve_method_parent'],
                        'posts_per_page'=>-1, 
                        'numberposts'=>-1
                    );
                    $posts = get_posts( $args );
                    foreach( $posts as $v ) {
                        
                        // @since 1.2.5
                        if( !isset( $atts['keywords_retrieve_method_value'] ) ) $atts['keywords_retrieve_method_value'] = 'slug';
                        if($atts['keywords_retrieve_method_value']=='slug'){
                            $data_value = $v->post_name;
                        }elseif($atts['keywords_retrieve_method_value']=='id'){
                            $data_value = $v->ID;
                        }else{
                            $data_value = $v->post_title;
                        }
                        $item = '<li data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr($v->post_title) . '">';
                        $item .= '<span class="super-wp-tag">' . $v->post_title . '</span>'; 
                        $item .= '</li>';
                        $items[] = $item;
                    }
                }
                if($atts['keywords_retrieve_method']=='csv') {
                    $delimiter = ',';
                    $enclosure = '"';
                    if( isset( $atts['keywords_retrieve_method_delimiter'] ) ) $delimiter = $atts['keywords_retrieve_method_delimiter'];
                    if( isset( $atts['keywords_retrieve_method_enclosure'] ) ) $enclosure = stripslashes($atts['keywords_retrieve_method_enclosure']);
                    $file = get_attached_file($atts['keywords_retrieve_method_csv']);
                    if($file){
                        $row = 1;
                        if (($handle = fopen($file, "r")) !== FALSE) {
                            while (($data = fgetcsv($handle, 10000, $delimiter, $enclosure)) !== FALSE) {
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
                                $item = '<li data-value="' . esc_attr($value) . '" data-search-value="' . esc_attr($title) . '">';
                                $item .= '<span class="super-wp-tag">' . $title . '</span>'; 
                                $item .= '</li>';
                                $items[] = $item;
                            }
                            fclose($handle);
                        }
                    }
                }
                if($atts['keywords_retrieve_method']=='tags') {
                    $tags = get_tags(
                        array(
                            'hide_empty'=>false
                        )
                    );
                    foreach ( $tags as $v ) {
                        if( !isset( $atts['keywords_retrieve_method_value'] ) ) $atts['keywords_retrieve_method_value'] = 'slug';
                        if( $atts['keywords_retrieve_method_value']=='slug' ) {
                            $data_value = $v->slug;
                        }elseif( $atts['keywords_retrieve_method_value']=='id' ) {
                            $data_value = $v->term_id;
                        }else{
                            $data_value = $v->name;
                        }
                        $item = '<li data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr($v->name) . '">';
                        $item .= '<span class="super-wp-tag">' . $v->name . '</span>'; 
                        $item .= '<span class="super-wp-tag-count">×&nbsp;' . $v->count . '</span>'; 
                        if( !empty($v->description) ) {
                            $item .= '<span class="super-wp-tag-desc">' . $v->description . '</span>'; 
                        }
                        $item .= '</li>';
                        $items[] = $item;
                    }
                }

                $result .= '<ul class="super-dropdown-ui super-autosuggest-tags-list">';
                $result .= '<li data-value="" data-search-value="" class="super-no-results">' . __( 'No matches found', 'super-forms' ) . '...</li>';
                foreach( $items as $k => $v ) {
                    $result .= $v;
                }
                $result .= '</ul>';

            }
        }

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        // @since 1.2.4
        if( $atts['enable_auto_suggest']=='true' ) {
            $result .= '<ul class="super-dropdown-ui">';
            foreach( $items as $v ) {
                $result .= $v;
            }
            $result .= '</ul>';
        }

        if( ($atts['enable_address_auto_complete']=='true') && (empty($atts['address_api_key'])) ) {
            $result .= '<strong style="color:red;">' . __( 'Please edit this field and enter your "Google API key" under the "Address auto complete" TAB', 'super-forms' ) . '</strong>';
        }

        $result .= self::element_footer($tag, $atts);
        return $result;

    }


    public static function textarea( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        $result  = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        
        // @since   1.1.8 - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = stripslashes( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = stripslashes( $_POST[$atts['name']] );
        }

        // @since   2.9.0 - autopopulate with last entry data
        if( isset( $entry_data[$atts['name']] ) ) {
            $atts['value'] = stripslashes( $entry_data[$atts['name']]['value'] );
        }


        // @since   1.0.6   - make sure this data is set
        if( !isset( $atts['value'] ) ) {
            $atts['value'] = '';
        }
        if($atts['value']!='') $atts['value'] = SUPER_Common::email_tags( $atts['value'], null, $settings );

        // @since 3.5.0 - add shortcode compatibility for default field value
        $atts['value'] = do_shortcode($atts['value']); 

        // @since   1.2.4
        if( !isset( $atts['editor'] ) ) $atts['editor'] = 'false';
        if( !isset( $atts['media_buttons'] ) ) $atts['media_buttons'] = 'true';
        if( !isset( $atts['wpautop'] ) ) $atts['wpautop'] = 'true';
        if( !isset( $atts['force_br'] ) ) $atts['force_br'] = 'false';
        if( !isset( $atts['height'] ) ) $atts['height'] = 0;
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
                wp_editor( $atts['value'], $atts['name'] . '-' . self::$current_form_id, $editor_settings );
                $editor_html = ob_get_clean();
                $common_attributes = self::common_attributes( $atts, $tag );
                $editor_html = str_replace( '></textarea>', $common_attributes . ' data-force-br="' . $atts['force_br'] . '" data-teeny="' . $atts['teeny'] . '" data-incl-url="' . $includes_url . '"></textarea>', $editor_html );
                $editor_html = str_replace( '<textarea', '<textarea id="' . $atts['name'] . '-' . self::$current_form_id . '"', $editor_html );
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
                wp_editor( $atts['value'], $atts['name'] . '-' . self::$current_form_id, $editor_settings );
                $editor_html = ob_get_clean();
                $common_attributes = self::common_attributes( $atts, $tag );
                $editor_html = str_replace( '<textarea','<textarea '.$common_attributes.' ', $editor_html );
                $editor_html = str_replace( '<textarea', '<textarea id="' . $atts['name'] . '-' . self::$current_form_id . '"', $editor_html );
                $result .= str_replace( 'super-shortcode-field', 'super-shortcode-field super-text-editor initialized', $editor_html );
            }
        }else{

            // @since 1.9 - custom class
            if( !isset( $atts['class'] ) ) $atts['class'] = '';

            $result .= '<textarea class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"';
            $result .= ' name="' . $atts['name'] . '"';
            if( $atts['height']>0 ) {
                $result .= ' style="min-height:' . $atts['height'] . 'px;" ';
            }
            $result .= self::common_attributes( $atts, $tag );

            // @since 3.6.0 - convert <br /> tags to \n
            $value = preg_replace('#<br\s*/?>#i', "\n", $atts['value']);
            $result .= ' >' . $value . '</textarea>';
        }

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= self::element_footer($tag, $atts);
        return $result;
    }
    public static function dropdown( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        // @since 3.5.0 - google distance calculation between 2 addresses for dropdown
        $data_attributes = '';
        $distance_calculator_class = '';
        if( !isset( $atts['enable_distance_calculator'] ) ) $atts['enable_distance_calculator'] = '';
        if( $atts['enable_distance_calculator']=='true' ) {
            if( !isset( $atts['distance_method'] ) ) $atts['distance_method'] = 'start';
            if( !isset( $atts['distance_value'] ) ) $atts['distance_value'] = 'distance';
            if( !isset( $atts['distance_units'] ) ) $atts['distance_units'] = 'metric';
            if( !isset( $atts['distance_field'] ) ) $atts['distance_field'] = '';
            $data_attributes .= ' data-distance-method="'.$atts['distance_method'].'"';
            $data_attributes .= ' data-distance-value="'.$atts['distance_value'].'"';
            $data_attributes .= ' data-distance-units="'.$atts['distance_units'].'"';
            $data_attributes .= ' data-distance-field="'.$atts['distance_field'].'"';
            if( $atts['distance_method']=='start' ) {
                $data_attributes .= ' data-distance-destination="'.$atts['distance_destination'].'"';
            }else{
                $data_attributes .= ' data-distance-start="'.$atts['distance_start'].'"';
            }
            $distance_calculator_class .= ' super-distance-calculator';
        }


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
                if( $v['checked']=='true' || $v['checked']==1 ) {
                    $selected_items[] = $v['value'];
                    if( $placeholder=='' ) {
                        $placeholder .= $v['label'];
                    }else{
                        $placeholder .= ', ' . $v['label'];
                    }
                    $items[] = '<li data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '" class="selected super-default-selected">' . stripslashes($v['label']) . '</li>'; 
                }else{
                    $items[] = '<li data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '">' . stripslashes($v['label']) . '</li>'; 
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
                $items[] = '<li data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr( $v->post_title ) . '">' . $v->post_title . '</li>'; 
            }
        }

        // Retrieve product attributes
        if($atts['retrieve_method']=='product_attribute') {
            if( !isset( $atts['retrieve_method_product_attribute'] ) ) $atts['retrieve_method_product_attribute'] = '';
            if($atts['retrieve_method_product_attribute']!=''){
                // Let's try to retrieve product attributes
                if ( class_exists( 'WooCommerce' ) ) {
                    global $post;
                    if( isset( $post ) ) {
                        global $product;
                        $attributes = $product->get_attribute( $atts['retrieve_method_product_attribute'] );
                        $attributes = explode(', ', $attributes);
                        foreach( $attributes as $v ) {
                        $items[] = '<li data-value="' . esc_attr( $v ) . '" data-search-value="' . esc_attr( $v ) . '">' . $v . '</li>'; 
                        }
                    }
                }          
            }
        }

        // @since 4.0.0 - retrieve current author data
        if($atts['retrieve_method']=='author') {

            $meta_field_name = ',';
            $line_explode = "\n";
            $option_explode = '|';
            if( !empty( $atts['retrieve_method_author_field'] ) ) $meta_field_name = $atts['retrieve_method_author_field'];
            if( !empty( $atts['retrieve_method_author_line_explode'] ) ) $line_explode = $atts['retrieve_method_author_line_explode'];
            if( !empty( $atts['retrieve_method_author_option_explode'] ) ) $option_explode = $atts['retrieve_method_author_option_explode'];

            // First check if we are on the author profile page, and see if we can find author based on slug
            // get_current_user_id()
            $page_url = ( isset($_SERVER['HTTPS']) ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $author_name = basename($page_url);
            $current_author = ( isset($_GET['author']) ? get_user_by('id', absint($_GET['author'])) : get_user_by('slug', $author_name) );
            if( $current_author ) {
                // This is an author profile page
                $author_id = $current_author->ID;
            }else{
                // This is not an author profile page
                global $post;
                if( !isset( $post ) ) {
                    $author_id = '';
                }else{
                    $author_id = $post->post_author;
                }
            }
            $data = get_user_meta( absint($author_id), $meta_field_name, true ); 
            if( $data ) {
                $data = explode( $line_explode, $data );
                if( is_array($data) ) {
                    foreach( $data as $v ) {
                        $values = explode( $option_explode , $v );
                        $label = ( isset( $values[0] ) ? $values[0] : '' );
                        $value = ( isset( $values[1] ) ? $values[1] : $label );
                        if( empty($label) ) continue;

                        // @since 4.0.2 - remove line breaks for dropdowns
                        $label = str_replace(array("\r", "\n"), '', $label);
                        $value = str_replace(array("\r", "\n"), '', $value);
                        
                        $items[] = '<li data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $label ) . '">' . $label . '</li>'; 
                    }
                }
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
                    while (($data = fgetcsv($handle, 10000, $delimiter, $enclosure)) !== FALSE) {
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

        // @since   4.4.1 - retrieve from custom database table
        if($atts['retrieve_method']=='db_table') {
            if( !isset( $atts['retrieve_method_db_table'] ) ) $atts['retrieve_method_db_table'] = '';
            if( !isset( $atts['retrieve_method_db_row_value'] ) ) $atts['retrieve_method_db_row_value'] = '';
            if( !isset( $atts['retrieve_method_db_row_label'] ) ) $atts['retrieve_method_db_row_label'] = '';
            $column_value = $atts['retrieve_method_db_row_value'];
            $column_label = $atts['retrieve_method_db_row_label'];

            //$str = '[{code}] - {last_name} {initials} - [{date}] - {email}';
            // Define the SELECT query
            $select_query = '';
            $regex = '/{\s?[\'|"|\s|]?(.*?)[\'|"|\s|]?}/';
            $match = preg_match_all($regex, $column_value, $matches, PREG_SET_ORDER, 0);
            $tags = array();
            foreach($matches as $k => $v){
                if(isset($v[1])) {
                    $tags[$v[1]] = $v[1];
                    $column_name = $v[1];
                    if(empty($select_query)){
                        $select_query .= $v[1];
                    }else{
                        $select_query .= ', '.$v[1];
                    }
                }
            }
            $match = preg_match_all($regex, $column_label, $matches, PREG_SET_ORDER, 0);
            foreach($matches as $k => $v){
                if(isset($v[1])) {
                    $tags[$v[1]] = $v[1];
                    $column_name = $v[1];
                    if(empty($select_query)){
                        $select_query .= $v[1];
                    }else{
                        $select_query .= ', '.$v[1];
                    }
                }
            }
            if($select_query=='') {
                $select_query = '*';
            }
            global $wpdb;
            $table = $atts['retrieve_method_db_table'];
            $results = $wpdb->get_results("SELECT $select_query FROM $table WHERE 1=1", ARRAY_A);
            foreach( $results as $k => $v ) {
                $final_value = $column_value;
                $final_label = $column_label;
                foreach( $tags as $tk => $tv ) {
                    $final_value = str_replace('{'.$tv.'}', $v[$tv], $final_value);
                    $final_label = str_replace('{'.$tv.'}', $v[$tv], $final_label);
                }
                $items[] = '<li data-value="' . esc_attr( $final_value ) . '" data-search-value="' . esc_attr( $final_label ) . '">' . $final_label . '</li>'; 
            }
        }

        // @since 4.2.0 - option to filter items of dropdowns, in case of custom post types or other filtering that needs to be done
        $items = apply_filters( 'super_' . $tag . '_' . $atts['name'] . '_items_filter', $items, array( 'tag'=>$tag, 'atts'=>$atts, 'settings'=>$settings, 'entry_data'=>$entry_data ) );

        if( $placeholder!='' ) {
            $atts['placeholder'] = $placeholder;
        }
        if( empty( $atts['placeholder'] ) ) {
            $atts['placeholder'] = $atts['dropdown_items'][0]['label'];
            $atts['value'] = $atts['dropdown_items'][0]['value'];
            $atts['dropdown_items'][0]['checked'] = true;
            $items[0] = '<li data-value="' . esc_attr( $atts['value'] ) . '" class="selected">' . $atts['placeholder'] . '</li>';     
        }
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

        $result .= '<input type="hidden" class="super-shortcode-field';
        $result .= $distance_calculator_class;
        $result .= ($atts['class']!='' ? ' ' . $atts['class'] : '');
        $result .= '"';
        $result .= ($atts['enable_distance_calculator']=='true' ? $data_attributes : '');
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        // @since 1.2.8     - auto scroll to value after key press
        if(empty($atts['disable_filter'])){
            $result .= '<input type="text" name="super-dropdown-search" value="" />';
        }

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<ul class="super-dropdown-ui' . $multiple . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '">';
        $result .= '<li data-value="" class="super-placeholder">' . $atts['placeholder'] . '</li>';
        foreach( $items as $v ) {
            $result .= $v;
        }
        $result .= '</ul>';
        $result .= '<span class="super-dropdown-arrow"></span>';

        $result .= self::element_footer($tag, $atts);
        return $result;

    }
    public static function dropdown_items( $tag, $atts ) {
        return '<li data-value="' . esc_attr( $atts['value'] ) . '">' . $atts['label'] . '</li>'; 
    }
    public static function checkbox( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        $classes = ' display-' . $atts['display'];
        $result = self::opening_tag( $tag, $atts, $classes );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        $items = array();
        
        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

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

        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        $checked_items = explode( ",", $atts['value'] );

        // @since   1.2.7
        if( !isset( $atts['retrieve_method'] ) ) $atts['retrieve_method'] = 'custom';
        if($atts['retrieve_method']=='custom') {
            foreach( $atts['checkbox_items'] as $k => $v ) {
                if( ((!empty($v['checked'])) && ($v['checked']!='false') ) && ($atts['value']=='') ) $checked_items[] = $v['value'];
                if( !isset( $v['image'] ) ) $v['image'] = '';
                if( $v['image']!='' ) {
                    $image = wp_get_attachment_image_src( $v['image'], 'original' );
                    $image = !empty( $image[0] ) ? $image[0] : '';
                    $item = '';

                    // @since 3.0.0 - checkbox width and height setting
                    if( !isset( $v['width'] ) ) $v['width'] = 150;
                    if( !isset( $v['height'] ) ) $v['height'] = 200;
                    $img_styles = '';
                    if( $v['width']!='' ) $img_styles .= 'width:' . $v['width'] . 'px;';
                    if( $v['height']!='' ) $img_styles .= 'height:' . $v['height'] . 'px;';
                    
                    $item .= '<label class="' . ( !in_array($v['value'], $checked_items) ? ' super-has-image' : 'super-has-image super-selected super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '">';
                    if( !empty( $image ) ) {
                        $item .= '<div class="image" style="background-image:url(\'' . $image . '\');"><img src="' . $image . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                    }else{
                        $image = SUPER_PLUGIN_FILE . 'assets/images/image-icon.png';
                        $item .= '<div class="image" style="background-image:url(\'' . $image . '\');"><img src="' . $image . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                    }
                    $item .= '<input' . ( !in_array($v['value'], $checked_items) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $v['value'] ) . '" />';
                    if($v['label']!='') $item .= '<span class="super-item-label">' . stripslashes($v['label']) . '</span>';
                    $item .='</label>';
                }else{
                    $item = '<label class="' . ( !in_array($v['value'], $checked_items) ? '' : 'super-selected super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input ' . ( (($v['checked']!=='true') && ($v['checked']!==true)) ? '' : 'checked="checked"' ) . ' type="checkbox" value="' . esc_attr( $v['value'] ) . '" />' . stripslashes($v['label']) . '</label>';
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

        // Retrieve product attributes
        if($atts['retrieve_method']=='product_attribute') {
            if( !isset( $atts['retrieve_method_product_attribute'] ) ) $atts['retrieve_method_product_attribute'] = '';
            if($atts['retrieve_method_product_attribute']!=''){
                // Let's try to retrieve product attributes
                if ( class_exists( 'WooCommerce' ) ) {
                    global $post;
                    if( isset( $post ) ) {
                        global $product;
                        $attributes = $product->get_attribute( $atts['retrieve_method_product_attribute'] );
                        $attributes = explode(', ', $attributes);
                        foreach( $attributes as $v ) {
                            $items[] = '<label class="' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input type="checkbox" value="' . esc_attr( $v ) . '" />' . $v . '</label>';
                        }
                    }
                }          
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
                    while (($data = fgetcsv($handle, 10000, $delimiter, $enclosure)) !== FALSE) {
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
                        $items[] = '<label class="' . ( !in_array($value, $checked_items) ? '' : 'super-selected super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input' . ( !in_array($value, $checked_items) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $value ) . '" />' . $title . '</label>';
                    }
                    fclose($handle);
                }
            }
        }

        // @since 4.2.0 - option to filter items of dropdowns, in case of custom post types or other filtering that needs to be done
        $items = apply_filters( 'super_' . $tag . '_' . $atts['name'] . '_items_filter', $items, array( 'tag'=>$tag, 'atts'=>$atts, 'settings'=>$settings, 'entry_data'=>$entry_data ) );

        foreach( $items as $v ) {
            $result .= $v;
        }

        $result .= '<input class="super-shortcode-field" type="hidden"';
        $result .= ' name="' . esc_attr( $atts['name'] ) . '" value="' . implode(',',$checked_items) . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= self::element_footer($tag, $atts);
        return $result;
    }
    public static function radio( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        $classes = ' display-' . $atts['display'];  
        $result = self::opening_tag( $tag, $atts, $classes );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        $items = array();
     
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

        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';     

        // @since   1.2.7
        if( !isset( $atts['retrieve_method'] ) ) $atts['retrieve_method'] = 'custom';
        if($atts['retrieve_method']=='custom') {
            $active_found = false;
            foreach( $atts['radio_items'] as $k => $v ) {
                if( ( (!empty($v['checked'])) && ($v['checked']!='false') ) && ($atts['value']=='') ) $atts['value'] = $v['value'];

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
                    if( !isset( $v['width'] ) ) $v['width'] = 150;
                    if( !isset( $v['height'] ) ) $v['height'] = 200;
                    $img_styles = '';
                    if( $v['width']!='' ) $img_styles .= 'width:' . $v['width'] . 'px;';
                    if( $v['height']!='' ) $img_styles .= 'height:' . $v['height'] . 'px;';

                    $result .= '<label class="' . ( $active!=true ? ' super-has-image' : 'super-has-image super-selected super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '">';
                    if( !empty( $image ) ) {
                        $result .= '<div class="image" style="background-image:url(\'' . $image . '\');"><img src="' . $image . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                    }else{
                        $image = SUPER_PLUGIN_FILE . 'assets/images/image-icon.png';
                        $result .= '<div class="image" style="background-image:url(\'' . $image . '\');"><img src="' . $image . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                    }
                    $result .= '<input ' . ( (($v['checked']!=='true') && ($v['checked']!==true)) ? '' : 'checked="checked"' ) . ' type="radio" value="' . esc_attr( $v['value'] ) . '" />';
                    if($v['label']!='') $result .= '<span class="super-item-label">' . stripslashes($v['label']) . '</span>';
                    $result .='</label>';

                }else{
                    $result .= '<label class="' . ( $active!=true ? '' : 'super-selected super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input ' . ( (($v['checked']!=='true') && ($v['checked']!==true)) ? '' : 'checked="checked"' ) . ' type="radio" value="' . esc_attr( $v['value'] ) . '" />' . stripslashes($v['label']) . '</label>';
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

        // Retrieve product attributes
        if($atts['retrieve_method']=='product_attribute') {
            if( !isset( $atts['retrieve_method_product_attribute'] ) ) $atts['retrieve_method_product_attribute'] = '';
            if($atts['retrieve_method_product_attribute']!=''){
                // Let's try to retrieve product attributes
                if ( class_exists( 'WooCommerce' ) ) {
                    global $post;
                    if( isset( $post ) ) {
                        global $product;
                        $attributes = $product->get_attribute( $atts['retrieve_method_product_attribute'] );
                        $attributes = explode(', ', $attributes);
                        foreach( $attributes as $v ) {
                            $items[] = '<label class="' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input type="radio" value="' . esc_attr( $v ) . '" />' . $v . '</label>';
                        }
                    }
                }          
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
                    while (($data = fgetcsv($handle, 10000, $delimiter, $enclosure)) !== FALSE) {
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

        // @since 4.2.0 - option to filter items of dropdowns, in case of custom post types or other filtering that needs to be done
        $items = apply_filters( 'super_' . $tag . '_' . $atts['name'] . '_items_filter', $items, array( 'tag'=>$tag, 'atts'=>$atts, 'settings'=>$settings, 'entry_data'=>$entry_data ) );
        
        foreach( $items as $v ) {
            $result .= $v;
        }

        $result .= '<input class="super-shortcode-field" type="hidden"';
        $result .= ' name="' . esc_attr( $atts['name'] ) . '" value="' . $atts['value'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= self::element_footer($tag, $atts);
        return $result;
    }
    public static function radio_items( $tag, $atts ) {
        return '<label><input ' . ( (($atts['checked']==='false') || ($atts['checked']===false)) ? '' : 'checked="checked"' ) . ' type="radio" value="' . esc_attr( $atts['value'] ) . '" />' . $atts['label'] . '</label>';
    }
    public static function file( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

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
        $extensions = explode('|', $atts['extensions']);
        foreach($extensions as $k => $v){
            $extensions[$v] = $v;
            unset($extensions[$k]);
        }
        foreach($extensions as $k => $v){
            $upercase = strtoupper($v);
            if( (ctype_lower($v)) && (!isset($extensions[$upercase])) ) {
               $extensions[$upercase] = $upercase;
            }
        }
        $extensions = implode('|', $extensions);

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
        $result .= '<input class="super-shortcode-field super-fileupload" type="file" name="files[]" data-file-size="' . $atts['filesize'] . '" data-upload-limit="' . $atts['upload_limit'] . '" data-accept-file-types="' . $extensions . '" data-url="' . SUPER_PLUGIN_FILE . 'uploads/php/"';
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
                if(isset($entry_data[$atts['name']]['files'])) {
                    foreach( $entry_data[$atts['name']]['files'] as $k => $v ) {
                        if( isset($v['url']) ) {
                            $result .= '<div data-name="' . $v['value'] . '" class="super-uploaded"';
                            $result .= ' data-url="' . $v['url'] . '"';
                            $result .= ' data-thumburl="' . $v['thumburl'] . '">';
                            $result .= '<span class="super-fileupload-name"><a href="' . $v['url'] . '" target="_blank">' . $v['value'] . '</a></span>';
                            $result .= '<span class="super-fileupload-delete">[x]</span>';
                            $result .= '</div>';
                        }
                    }
                }
            }
        $result .= '</div>';

        $result .= self::element_footer($tag, $atts);
        return $result;
    }
    public static function date( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        
        wp_enqueue_script( 'jquery-ui-datepicker', false, array( 'jquery' ), SUPER_VERSION );
        wp_enqueue_script( 'super-date-format', SUPER_PLUGIN_FILE . 'assets/js/frontend/date-format.min.js' );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<input class="super-shortcode-field super-datepicker' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="text" autocomplete="off" ';
        $format = $atts['format'];
        if( $format=='custom' ) $format = $atts['custom_format'];

        // @since 1.1.8 - added option to select an other datepicker to achieve date range with 2 datepickers (useful for booking forms)
        if( !isset( $atts['connected_min'] ) ) $atts['connected_min'] = '';
        if( !isset( $atts['connected_max'] ) ) $atts['connected_max'] = '';

        // @since 1.2.5 - added option to add or deduct days based on connected datepicker
        if( !isset( $atts['connected_min_days'] ) ) $atts['connected_min_days'] = '1';
        if( !isset( $atts['connected_max_days'] ) ) $atts['connected_max_days'] = '1';

        if( !isset( $atts['range'] ) ) $atts['range'] = '-100:+5';
        if( !isset( $atts['first_day'] ) ) $atts['first_day'] = '1'; // @since 3.1.0 - start day of the week

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
            $atts['value'] = date_i18n($new_format);
        }

        /*

        // Javascript date format parameters:
        yy = short year
        yyyy = long year
        M = month (1-12)
        MM = month (01-12)
        MMM = month abbreviation (Jan, Feb ... Dec)
        MMMM = long month (January, February ... December)
        d = day (1 - 31)
        dd = day (01 - 31)
        ddd = day of the week in words (Monday, Tuesday ... Sunday)
        E = short day of the week in words (Mon, Tue ... Sun)
        D - Ordinal day (1st, 2nd, 3rd, 21st, 22nd, 23rd, 31st, 4th...)
        h = hour in am/pm (0-12)
        hh = hour in am/pm (00-12)
        H = hour in day (0-23)
        HH = hour in day (00-23)
        mm = minute
        ss = second
        SSS = milliseconds
        a = AM/PM marker
        p = a.m./p.m. marker
        var $parse_format = ["dd-MM-yyyy","dd/MM/yyyy","yyyy-MM-dd","dd MMM, yy","dd MMMM, yy","ddd, d MMMM, yyyy","MMddyyyy","MMddyy","M/d/yyyy","M/d/yy","MM/dd/yy","MM/dd/yyyy"];
        */


        /*
        if($format=='dd-mm-yy') $jsformat = 'dd-MM-yyyy';
        if($format=='dd-mm-yy') $jsformat = 'dd/MM/yyyy';
        if($format=='dd-mm-yy') $jsformat = 'yyyy-MM-dd';
        if($format=='dd-mm-yy') $jsformat = 'dd MMM, yy';
        if($format=='dd-mm-yy') $jsformat = 'dd MMMM, yy';
        if($format=='dd-mm-yy') $jsformat = 'ddd, d MMMM, yyyy';
        if($format=='dd-mm-yy') $jsformat = 'MMddyyyy';
        if($format=='dd-mm-yy') $jsformat = 'MMddyy';
        if($format=='dd-mm-yy') $jsformat = 'M/d/yyyy';
        if($format=='dd-mm-yy') $jsformat = 'M/d/yy';
        if($format=='dd-mm-yy') $jsformat = 'MM/dd/yy';
        if($format=='dd-mm-yy') $jsformat = 'MM/dd/yyyy';
        if($format=='dd-mm-yy') $jsformat = 'd MMM, yy';
        if($format=='dd-mm-yy') $jsformat = 'dddd, d MMM, yyyy';
        if($format=='dd-mm-yy') $jsformat = 'dddd, dd.MM.yyyy';
        if($format=='dd-mm-yy') $jsformat = 'dd-MM-yyyy';
        if($format=='dd/mm/yy') $jsformat = 'dd/MM/yyyy';
        if($format=='mm/dd/yy') $jsformat = 'MM/dd/yyyy';
        if($format=='yy-mm-dd') $jsformat = 'yyyy-MM-dd';
        if($format=='d M, y') $jsformat = 'd MMM, yy';
        if($format=='d MM, y') $jsformat = 'd MMMM, yy';
        if($format=='DD, d MM, yy') $jsformat = 'dddd, d MMMM, yyyy';
        if($format=='DD, dd.mm.yy') $jsformat = 'dddd, dd.MM.yyyy';
        */

        //$jsformat = 'dd-MM-yyyy';
        $jsformat = $format;
        $jsformat = str_replace('DD', 'dddd', $jsformat);
        if (preg_match("/MM/i", $jsformat)) {
            $jsformat = str_replace('MM', 'MMMM', $jsformat);
        }else{
            if (preg_match("/M/i", $jsformat)) {
                $jsformat = str_replace('M', 'MMM', $jsformat);
            }
        }
        $jsformat = str_replace('mm', 'MM', $jsformat);
        if (preg_match("/yy/i", $jsformat)) {
            $jsformat = str_replace('yy', 'yyyy', $jsformat);
        }else{
            if (preg_match("/y/i", $jsformat)) {
                $jsformat = str_replace('y', 'yy', $jsformat);
            }
        }

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

        // @since 3.5.0 - add shortcode compatibility for default field value
        $atts['value'] = do_shortcode($atts['value']); 

        $result .= ' value="' . $atts['value'] . '" 
        name="' . $atts['name'] . '" 
        data-format="' . $format . '" 
        data-jsformat="' . $jsformat. '" 
        data-connected_min="' . $atts['connected_min'] . '" 
        data-connected_min_days="' . $atts['connected_min_days'] . '" 
        data-connected_max="' . $atts['connected_max'] . '" 
        data-connected_max_days="' . $atts['connected_max_days'] . '" 
        data-range="' . $atts['range'] . '" 
        data-first-day="' . $atts['first_day'] . '" ';

        // @since 1.5.0 - Allow work days selection
        if( !empty($atts['work_days']) ) {
            $result .= 'data-work-days="true"';
        }
        // @since 1.5.0 - Allow weekend selection
        if( !empty($atts['weekends']) ) {
            $result .= 'data-weekends="true"';
        }

        // @since 3.6.0 - Exclude specific days
        if( !empty($atts['excl_days'])) {
            $result .= 'data-excl-days="' . $atts['excl_days'] . '"';
        }

        $result .= self::common_attributes( $atts, $tag );
        $result .= ' readonly="true" />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= self::element_footer($tag, $atts);
        return $result;
    }
    public static function time( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        wp_enqueue_script( 'jquery-timepicker', SUPER_PLUGIN_FILE . 'assets/js/frontend/timepicker.min.js' );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

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

        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        // @since 3.5.0 - add shortcode compatibility for default field value
        $atts['value'] = do_shortcode($atts['value']); 

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
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= self::element_footer($tag, $atts);
        return $result;
    }    
    public static function rating( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        $result .= '<div class="super-rating">';

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

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
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';

        $result .= self::element_footer($tag, $atts);
        return $result;
    }
    public static function skype( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        wp_enqueue_script( 'super-skype', 'https://secure.skypeassets.com/i/scom/js/skype-uri.js' );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        if( !isset( $atts['username'] ) ) $atts['username'] = '';
        $result .= '<div id="SkypeButton_Call_' . $atts['username'] . '" class="super-skype-button" data-username="' . $atts['username'] . '" data-method="' . $atts['method'] . '" data-color="' . $atts['color'] . '" data-size="' . $atts['size'] . '"></div>';

        $result .= self::element_footer($tag, $atts);
        return $result;
    }
    public static function countries( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        $multiple = '';
        if( !isset( $atts['maxlength'] ) ) $atts['maxlength'] = 0;
        if( !isset( $atts['minlength'] ) ) $atts['minlength'] = 0;
        if( ($atts['minlength']>1) || ($atts['maxlength']>1) ) $multiple = ' multiple';

        // @since   1.1.8 - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = sanitize_text_field( $_POST[$atts['name']] );
        }
        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        // @since 3.5.0 - add shortcode compatibility for default field value
        $atts['value'] = do_shortcode($atts['value']); 

        $result .= '<input class="super-shortcode-field" type="hidden"';
        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag );

        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        // @since 1.2.8     - auto scroll to value after key press
        if(empty($atts['disable_filter'])){
            $result .= '<input type="text" name="super-dropdown-search" value="" />';
        }

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

        $result .= self::element_footer($tag, $atts);
        return $result;
    }
    public static function password( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since   1.1.8 - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = sanitize_text_field( $_POST[$atts['name']] );
        }
        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<input class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="password"';
        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= self::element_footer($tag, $atts);
        return $result;
    }
    public static function hidden( $tag, $atts, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        $classes = ' hidden';
        $result = self::opening_tag( $tag, $atts, $classes );

        // @since   1.1.8 - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = sanitize_text_field( $_POST[$atts['name']] );
        }

        // @since   3.0.0 - also allow tags for hidden fields 
        if( !isset( $atts['value'] ) ) {
            $atts['value'] = '';
        }
        if($atts['value']!='') $atts['value'] = SUPER_Common::email_tags( $atts['value'], null, $settings );

        // @since 3.5.0 - add shortcode compatibility for default field value
        $atts['value'] = do_shortcode($atts['value']); 

        if( !isset( $atts['exclude'] ) ) $atts['exclude'] = 0;
        if( !isset( $atts['exclude_entry'] ) ) $atts['exclude_entry'] = '';

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

        $result .= '<input class="super-shortcode-field" type="hidden"';
        if( !empty($atts['name']) ) $result .= ' name="' . $atts['name'] . '"';
        if( !empty($atts['value']) ) $result .= ' value="' . $atts['value'] . '" data-default-value="' . $atts['value'] . '"';
        if( !empty($atts['email']) ) $result .= ' data-email="' . $atts['email'] . '"';
        if( !empty($atts['exclude']) ) $result .= ' data-exclude="' . $atts['exclude'] . '"';
        if( !empty($atts['exclude_entry']) ) $result .= ' data-exclude-entry="' . $atts['exclude_entry'] . '"';
        if( $atts['enable_random_code']=='true' ) $result .= ' data-code="' . $atts['enable_random_code'] . '"';
        if( $atts['code_invoice']=='true' ) $result .= ' data-invoice-padding="' . $atts['code_invoice_padding'] . '"';
        $result .= ' />';

        $result .= self::loop_variable_conditions( $atts );
        $result .= '</div>';
        return $result;
    }

    public static function recaptcha( $tag, $atts ) {
        
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        wp_enqueue_script('super-recaptcha', 'https://www.google.com/recaptcha/api.js?onload=SUPERreCaptcha&render=explicit');
        $settings = get_option('super_settings');
        $result = self::opening_tag( $tag, $atts );
        if( empty( $settings['form_recaptcha'] ) ) $settings['form_recaptcha'] = '';
        if( empty( $settings['form_recaptcha_secret'] ) ) $settings['form_recaptcha_secret'] = '';
        if( isset( $atts['error'] ) ) $atts['error'] = '';
        if( isset( $atts['align'] ) ) $atts['align'] = '';
        if( !empty( $atts['align'] ) ) $atts['align'] = ' align-' . $atts['align'];
        $result .= '<div class="super-recaptcha' . $atts['align'] . '" data-key="' . $settings['form_recaptcha'] . '" data-message="' . $atts['error'] . '"></div>';
        if( ( $settings['form_recaptcha']=='' ) || ( $settings['form_recaptcha_secret']=='' ) ) {
            $result .= '<strong style="color:red;">' . __( 'Please enter your reCAPTCHA key and secret in (Super Forms > Settings > Form Settings)', 'super-forms' ) . '</strong>';
        }
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }

    public static function image( $tag, $atts ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'html_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        
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

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'html_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        $result = self::opening_tag( $tag, $atts );
        if( !empty($atts['title']) ) {
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
            $result .= stripslashes($atts['title']);
            $result .= '</'.$atts['size'].'>';
            $result .= '</div>';
        }
        if( !empty($atts['desc']) ) {
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
            $result .= stripslashes($atts['desc']);
            $result .= '</div>';
        }
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }

    public static function html( $tag, $atts ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'html_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

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
        if(!isset($atts['html'])) $atts['html'] = '';
        if( $atts['html']!='' ) { 
            
            // @since 2.3.0 - speed improvements for replacing {tags} in HTML fields
            preg_match_all('/{\K[^}]*(?=})/m', $atts['html'], $matches);
            
            // @since 3.8.0 - strip the advanced tags and only return the field name
            $data_fields = array();
            foreach($matches[0] as $k => $v){
                $v = explode(";", $v);
                $data_fields[$v[0]] = $v[0];
            }
            $fields = implode('][', $data_fields);

            $html = $atts['html'];
            
            // @since 4.2.0 - automatically convert linebreaks to <br />
            if( !empty($atts['nl2br']) ) {
                $html = nl2br($html);
            }

            $result .= '<div class="super-html-content' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" data-fields="[' . $fields . ']">' . do_shortcode( stripslashes($html) ) . '</div>';
            
            $result .= '<textarea>' . do_shortcode( stripslashes($html) ) . '</textarea>';
        }
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }

    public static function divider( $tag, $atts ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'html_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

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

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'html_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

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
     *  Google Map with API options
     *
     *  @since      3.5.0
    */
    public static function google_map( $tag, $atts ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'html_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        $map_styles = 'min-width:'.$atts['min_width'].'px;';
        $map_styles .= 'min-height:'.$atts['min_height'].'px;';
        if( !empty( $atts['max_width'] ) ) {
            $map_styles .= 'max-width:'.$atts['max_width'].'px;';
        }else{
            $map_styles .= 'max-width:100%';
        }
        if( !empty( $atts['max_height'] ) ) {
            $map_styles .= 'max-height:'.$atts['max_height'].'px;';
        }else{
            $map_styles .= 'max-height:100%';
        }

        if(empty($atts['api_key'])) $atts['api_key'] = '';
        wp_enqueue_script( 'super-google-maps-api', 'https://maps.googleapis.com/maps/api/js?key=' . $atts['api_key'] . '&libraries=drawing,geometry,places,visualization&callback=SUPER.google_maps_init', array( 'super-common' ), SUPER_VERSION, false );

        // Add field attributes if {tags} are being used
        $fields = array();

        if( !empty($atts['enable_polyline']) ) {
            $polylines = explode("\n", $atts['polylines']);
            foreach( $polylines as $k => $v ) {
                $coordinates = explode("|", $v);
                if( count($coordinates)<2 ) {
                    $error = __( 'Incorrect latitude and longitude coordinates for Polylines, please correct and update element!', 'super-forms' );
                }else{
                    $lat = $coordinates[0];
                    $lng = $coordinates[1];
                    if( preg_match("/{(.*?)}/", $lat) ) {
                        $origin_name = str_replace("{", "",$lat);
                        $origin_name = str_replace("}", "", $origin_name);
                        $fields[$origin_name] = $origin_name;
                    }
                    if( preg_match("/{(.*?)}/", $lng) ) {
                        $origin_name = str_replace("{", "",$lng);
                        $origin_name = str_replace("}", "", $origin_name);
                        $fields[$origin_name] = $origin_name;
                    }
                }
            }
        }

        // @since 3.7.0 - add address {tags} to the data-fields attribute
        preg_match_all('/{\K[^}]*(?=})/m', $atts['address'], $matches);
        $fields = array_unique(array_merge($fields, $matches[0]), SORT_REGULAR);

        $map_id = 'super-google-map-' . self::$current_form_id;
        $fields = implode('][', $fields);
        $result = '<div class="super-google-map" data-fields="[' . $fields . ']">';
        if( (is_admin()) && (!empty($error)) ) {
            $result .= '<p><strong style="color:red;">' . $error . '</strong></p>';
        }
        $result .= '<div class="' . $map_id . '" id="' . $map_id . '" style="' . $map_styles . '">';
        if( empty($atts['api_key']) ) {
            $result .= '<strong style="color:red;">' . __( 'Please enter your "Google API key" and make sure you enabled the "Google Maps JavaScript API" library in order to generate a map', 'super-forms' ) . '</strong>';
        }
        $result .= '</div>';
        $result .= '<textarea disabled class="super-hidden">' . json_encode( $atts ) . '</textarea>';
        $result .= '</div>';
        return $result;

        // Draw Polylines
        /*
        $polylines_js = '';
        if( $atts['enable_polyline']=='true' ) {
            $polylines = explode("\n", $atts['polylines']);
            $polylines_js .= 'var Coordinates = [';
            $lat_min = '';
            foreach( $polylines as $k => $v ) {
                $coordinates = explode("|", $v);
                $lat = $coordinates[0];
                $lng = $coordinates[1];
                if( count($polylines)==($k+1) ) {
                    $polylines_js .= "{lat: $lat, lng: $lng}";
                }else{
                    $polylines_js .= "{lat: $lat, lng: $lng},";
                }
                if( $lat_min=='' ) {
                    $lat_min = $lat;
                    $lat_max = $lat;
                    $lng_min = $lng;
                    $lng_max = $lng;
                } 
                if($lat_min>$lat) $lat_min = $lat;
                if($lat_max<$lat) $lat_max = $lat;
                if($lng_min>$lng) $lng_min = $lng;
                if($lng_max<$lng) $lng_max = $lng;
            }
            $polylines_js .= '];';
            $polylines_js .= '

            //Example values of min & max latlng values
            var lat_min = ' . $lat_min . ';
            var lat_max = ' . $lat_max . ';
            var lng_min = ' . $lng_min . ';
            var lng_max = ' . $lng_max . ';

            map.setCenter(new google.maps.LatLng(
              ((lat_max + lat_min) / 2.0),
              ((lng_max + lng_min) / 2.0)
            ));
            map.fitBounds(new google.maps.LatLngBounds(
              //bottom left
              new google.maps.LatLng(lat_min, lng_min),
              //top right
              new google.maps.LatLng(lat_max, lng_max)
            ));
            var Path = new google.maps.Polyline({
              path: Coordinates,
              geodesic: false,
              strokeColor: \'#FF0000\',
              strokeOpacity: 1.0,
              strokeWeight: 2
            });
            Path.setMap(map);';
        }

        $result .= '<script>
            function initMap() {
                var map = new google.maps.Map(document.getElementById(\'' . $map_id . '\'), {
                  zoom: ' . $atts['zoom'] . '
                  //mapTypeId: \'terrain\'
                });

                ' . $polylines_js . '
            }
            </script>';
        */
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

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        // Make sure the default form button won't be returned, since we are using a custom button
        if( !isset( $GLOBALS['super_custom_button_used'] ) ) {
            $GLOBALS['super_custom_button_used'] = true;
        }

        $name = $settings['form_button'];

        // @since 2.0.0 - button action (submit/clear/redirect)
        $action = 'submit';

        // @since 2.0.0 - button loading state text
        $loading = '';
        if( !empty($settings['form_button_loading']) ) {
            $loading = $settings['form_button_loading'];
        }

        $radius = $settings['form_button_radius'];
        $type = $settings['form_button_type'];
        $size = $settings['form_button_size'];
        $align = $settings['form_button_align'];
        $width = $settings['form_button_width'];
        $icon_option = (isset($settings['form_button_icon_option']) ? $settings['form_button_icon_option'] : 'none');
        $icon_visibility = (isset($settings['form_button_icon_visibility']) ? $settings['form_button_icon_visibility'] : 'visible');
        $icon_animation = (isset($settings['form_button_icon_animation']) ? $settings['form_button_icon_animation'] : 'horizontal');
        $icon = (isset($settings['form_button_icon']) ? $settings['form_button_icon'] : '');
        $color = $settings['theme_button_color'];
        $color_hover = $settings['theme_button_color_hover'];
        $font = $settings['theme_button_font'];
        $font_hover = $settings['theme_button_font_hover'];

        if( !empty( $atts['action']) ) $action = $atts['action'];
        if( !empty( $atts['name'] ) ) $name = $atts['name'];

        if( !empty( $atts['loading'] ) ) $loading = $atts['loading'];
        
        // @since 3.4.0 - entry status
        if( !isset( $atts['entry_status'] ) ) $atts['entry_status'] = '';
        if( !isset( $atts['entry_status_update'] ) ) $atts['entry_status_update'] = '';
        
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
                if( !empty( $atts['target'] ) ) $atts['target'] = 'data-target="' . $atts['target'] . '" ';
            }
            
            //$result .= '<a ' . $atts['target'] . 'href="' . $url . '" class="no_link' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '">';
            $result .= '<div ' . $atts['target'] . 'data-href="' . $url . '" class="super-button-wrap no_link' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '">';
                $result .= '<div class="super-button-name" data-action="' . $action . '" data-status="' . $atts['entry_status'] . '" data-status-update="' . $atts['entry_status_update'] . '" data-loading="' . $loading . '">';
                    $icon_html = '';
                    if( ( $icon!='' ) && ( $icon_option!='none' ) ) {
                        $icon_html = '<i class="fa fa-' . $icon . '"></i>';
                    }
                    if( $icon_option=='left' ) $result .= $icon_html;
                    $result .= stripslashes($name);
                    if( $icon_option=='right' ) $result .= $icon_html;

                    // @since 3.9.0 - option for print action to use custom HTML
                    if( !empty($atts['print_custom']) ) {
                        if( !empty($atts['print_file']) ) {
                            $result .= '<input type="hidden" name="print_file" value="' . absint($atts['print_file']) . '" />';
                        }
                    }

                $result .= '</div>';
                $result .= '<span class="super-after"></span>';
            $result .= '</div>';
            //$result .= '</a>';
        $result .= '</div>';
        return $result;
    }


    /** 
     *  Output the shortcode element on front-end
     *
     * @param  string  $tag
     * @param  string  $group
     * @param  array   $data
     * @param  array   $shortcodes
     *
     *  @since      1.0.0
    */
    public static function output_element_html( $tag, $group, $data, $inner, $shortcodes=null, $settings=null, $entry_data=null ) {
        
        // @since 3.5.0 - backwards compatibility with older form codes that have image field and other HTML field in group form_elements instead of html_elements
        if( ($group=='form_elements') && ($tag=='image' || $tag=='heading' || $tag=='html' || $tag=='divider' || $tag=='spacer' || $tag=='google_map' ) ) {
            $group = 'html_elements';
        }

        if( $shortcodes==null ) {
            $shortcodes = self::shortcodes();
        }
        if( !isset( $shortcodes[$group]['shortcodes'][$tag] ) ) {
            return '';
        }

        $callback = $shortcodes[$group]['shortcodes'][$tag]['callback'];
        // Only if element has a callback function we will call it, we don't need to do this for predefined elements
        if($callback){
            $callback = explode( '::', $callback );
            $class = $callback[0];
            $function = $callback[1];
            $data = json_decode(json_encode($data), true);
            $inner = json_decode(json_encode($inner), true);
            return call_user_func( array( $class, $function ), $tag, $data, $inner, $shortcodes, $settings, $entry_data );
        }
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

            $return .= '<div class="builder-html" style="display:none;">';
            $return .= self::output_builder_html( $shortcode, $group, array(), array(), null, null, false);
            $return .= '</div>';

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
            'name' => __( 'Unique field name', 'super-forms' ) . ' *', 
            'desc' => __( 'Must be an unique name (required)', 'super-forms' ),
            'default' => ( !isset( $attributes['name'] ) ? $default : $attributes['name'] ),
            'required' => true,
            'filter' => true
        );
        return $array;
    }
    public static function email( $attributes=null, $default='' ) {
        $array = array(
            'name' => __( 'Email Label', 'super-forms' ) . ' *', 
            'desc' => __( 'Indicates the field in the email template. (required)', 'super-forms' ),
            'default' => ( !isset( $attributes['email'] ) ? $default : $attributes['email'] ),
        );
        return $array;
    }
    public static function label( $attributes=null, $default='' ) {
        $array = array(
            'name' => __( 'Field Label', 'super-forms' ), 
            'desc' => __( 'Will be visible in front of your field.', 'super-forms' ).' ('.__( 'leave blank to remove', 'super-forms' ).')',
            'default' => ( !isset( $attributes['label'] ) ? $default : $attributes['label'] ),
        );
        return $array;
    }    
    public static function description( $attributes=null, $default='') {
        $array = array(
            'name' => __( 'Field description', 'super-forms' ), 
            'desc' => __( 'Will be visible in front of your field.', 'super-forms' ).' ('.__( 'leave blank to remove', 'super-forms' ).')',
            'default' => ( !isset( $attributes['description'] ) ? $default : $attributes['description'] ),
        );
        return $array;
    }
    public static function icon( $attributes=null, $default='user' ) {
        $icon = array(
            'default' => ( !isset( $attributes['icon'] ) ? $default : $attributes['icon'] ),
            'name' => __( 'Select an Icon', 'super-forms' ), 
            'type' => 'icon',
            'desc' => __( 'Leave blank if you prefer to not use an icon.', 'super-forms' )
        );
        return $icon;
    }
    public static function placeholder( $attributes=null, $default=null ) {
        $array = array(
            'default' => ( !isset( $attributes['placeholder'] ) ? $default : $attributes['placeholder'] ),
            'name' => __( 'Placeholder', 'super-forms' ), 
            'desc' => __( 'Indicate what the user needs to enter or select. (leave blank to remove)', 'super-forms' )
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
        
        $result = '';

        // @since 2.1.0 - make sure we reset the grid system
        unset($GLOBALS['super_grid_system']);

        extract( shortcode_atts( array(
            'id' => '',
        ), $atts ) );

        // Sanitize the ID
        $form_id = absint($id);

        self::$current_form_id = $form_id;

        // Check if the post exists
        if ( FALSE === get_post_status( $form_id ) ) {
            // The post does not exist
            $result = '<strong>'.__('Error', 'super-forms' ).':</strong> '.sprintf(__('Super Forms could not find a form with ID: %d', 'super-forms' ), $form_id);
            return $result;
        }else{
            // Check if the post is a super_form post type
            $post_type = get_post_type($form_id);
            if( $post_type!='super_form' ) {
                    $result = '<strong>'.__('Error', 'super-forms' ).':</strong> '.sprintf(__('Super Forms could not find a form with ID: %d', 'super-forms' ), $form_id);
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
        $array['id'] = $form_id;
        
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

        $form_settings = get_post_meta( $form_id, '_super_form_settings', true );

        if(!isset(SUPER_Forms()->global_settings )){
            SUPER_Forms()->global_settings = get_option( 'super_settings' );
        }
        $global_settings = SUPER_Forms()->global_settings;

        if( $form_settings!=false ) {
            // @since 4.0.0 - when adding new field make sure we merge settings from global settings with current form settings
            foreach( $form_settings as $k => $v ) {
                if( isset( $global_settings[$k] ) ) {
                    if( $global_settings[$k] == $v ) {
                        unset( $form_settings[$k] );
                    }
                }
            }
        }else{
            $form_settings = array();
        }
        $settings = array_merge($global_settings, $form_settings);

        $settings = apply_filters( 'super_form_settings_filter', $settings, array( 'id'=>$form_id ) );
        SUPER_Forms()->enqueue_element_styles();
        SUPER_Forms()->enqueue_element_scripts($settings);

        // Get form elements
        $elements = get_post_meta( $form_id, '_super_elements', true );
        // Make sure it's converted into an array
        if( !is_array($elements) ) {
            $elements = json_decode( $elements, true );
        }

        // Enqueue Google Fonts if required
        if( !empty( $elements ) ) {
            SUPER_Forms()->enqueue_google_fonts($elements, SUPER_Forms()->fonts);        
        }

        $styles = '';

        // @since 1.3
        if( !empty( $settings['theme_form_margin'] ) ) {
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
        if( !empty( $settings['theme_field_transparent'] ) ) {
            $class .= ' super-transparent-fields';
        }

        // @since 1.2.8     - RTL support
        if( !empty( $settings['theme_rtl'] ) ) {
            $class .= ' super-rtl';
        }

        // @since 3.6.0     - Center form
        if( !empty( $settings['theme_center_form'] ) ) {
            $class .= ' super-center-form';
        }

        // @since 3.2.0     - Save form progress
        if( !empty( $settings['save_form_progress'] ) ) {
            $class .= ' super-save-progress';
        }

        // Always load the default styles (these can be overwritten by the above loaded style file
        $style_content .= require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/style-default.php' );
      
        $contact_entry_id = 0;
        if( isset( $_GET['contact_entry_id'] ) ) {
            $contact_entry_id = absint($_GET['contact_entry_id']);
        }else{
            if( isset( $_POST['contact_entry_id'] ) ) {
                $contact_entry_id = absint($_POST['contact_entry_id']);
            }
        }

        // @since 2.9.0 - autopopulate form with user last submitted entry data
        $entry_data = null;
        if( ( isset( $settings['retrieve_last_entry_data'] ) ) && ( $settings['retrieve_last_entry_data']=='true' ) ) {

            // @since 3.8.0 - retrieve entry data based on $_GET['contact_entry_id'] or $_POST['contact_entry_id'] 
            if( !empty($contact_entry_id) ) {
                $entry_data = get_post_meta( $contact_entry_id, '_super_contact_entry_data', true );
                unset($entry_data['hidden_form_id']);
            }else{
                $current_user_id = get_current_user_id();
                if( $current_user_id!=0 ) {
                    $form_ids = array($form_id);
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
        }

        // @since 3.2.0 - if entry data was not found based on user last entry, proceed and check if we need to get form progress for this form
        if( ($entry_data==null) && ( (isset($settings['save_form_progress'])) && ($settings['save_form_progress']=='true') ) ) {
            $form_progress = SUPER_Forms()->session->get( 'super_form_progress_' . $form_id );
            if($form_progress!=false){
                $entry_data = $form_progress;
            }
        }

        
        $result .= '<style type="text/css">.super-form-' . $form_id . ' > * {visibility:hidden;}</style>';
        $result .= '<div id="super-form-' . $form_id . '" '; 
        $result .= $styles;
        $result .= 'class="super-form ';
        $result .= ( $settings['form_preload'] == 0 ? 'preload-disabled ' : '' );
        $result .= 'super-form-' . $form_id;
        $result .= ' ' . $class;
        $result .= '"';
        $result .= ( (isset($settings['form_hide_after_submitting'])) && ($settings['form_hide_after_submitting']=='true') ? ' data-hide="true"' : '' );
        $result .= ( (isset($settings['form_clear_after_submitting'])) && ($settings['form_clear_after_submitting']=='true') ? ' data-clear="true"' : '' );
        
        // @since 3.3.0     - Disable submission on "Enter" 
        $result .= ( (isset($settings['form_disable_enter'])) && ($settings['form_disable_enter']=='true') ? ' data-disable-enter="true"' : '' );

        $result .= ' data-field-size="' . $settings['theme_field_size'] . '">'; 
        
        // @since 3.6.0 - for max-width of the form, needed for corectly centering form since new "Center form" option
        $form_styles = '';
        if( !empty( $settings['theme_max_width'] ) ) {
            $form_styles .= 'max-width:' . $settings['theme_max_width'] . 'px;';
        }
        if($form_styles!='') {
            $form_styles = ' style="' . $form_styles . '"';
        }

        // @since 1.8 - needed for autocomplete
        $result .= '<form autocomplete="on"' . $form_styles;

        // @since 3.6.0 - custom POST parameters method
        if( empty($settings['form_post_custom']) ) $settings['form_post_custom'] = '';

        // @since 2.2.0 - custom POST method
        if( ( isset( $settings['form_post_option'] ) ) && ( $settings['form_post_option']=='true' ) && ( $settings['form_post_custom']!='true' ) ) {
            $result .= ' method="post" action="' . $settings['form_post_url'] . '">';
            $result .= '<textarea class="super-hidden" name="json_data"></textarea>';
        }else{
            $result .= '>';
        }

        // @since 3.4.0 - Lock form after specific amount of submissions (based on total contact entries created)
        if( !empty($settings['form_locker']) ) {
            if( !isset($settings['form_locker_limit']) ) $settings['form_locker_limit'] = 0;
            $limit = $settings['form_locker_limit'];
            $count = get_post_meta( $form_id, '_super_submission_count', true );
            $display_msg = false;
            if( $count>=$limit ) {
                $display_msg = true;
            }
            if( !empty($settings['form_locker_reset']) ) {
                // Check if we need to reset the lock counter based on locker reset
                $last_date = get_post_meta( $form_id, '_super_last_submission_date', true );
                $reset = $settings['form_locker_reset'];
                switch ($reset) {
                    case 'daily':
                        $current_date = (int)date_i18n('Yz');
                        $last_date = (int)date_i18n('Yz', strtotime($last_date));
                        break;
                    case 'weekly':
                        $current_date = (int)date_i18n('YW');
                        $last_date = (int)date_i18n('YW', strtotime($last_date));
                        break;
                    case 'monthly':
                        $current_date = (int)date_i18n('Yn');
                        $last_date = (int)date_i18n('Yn', strtotime($last_date));
                        break;
                    case 'yearly':
                        $current_date = (int)date_i18n('Y');
                        $last_date = (int)date_i18n('Y', strtotime($last_date));
                        break;
                }
                if($current_date>$last_date){
                    // Reset locker
                    update_post_meta( $form_id, '_super_submission_count', 0 );
                    $display_msg = false;
                }
            }
            if( $display_msg ) {
                $result .= '<div class="super-msg super-error">';
                if($settings['form_locker_msg_title']!='') {
                    $result .= '<h1>' . $settings['form_locker_msg_title'] . '</h1>';
                }
                $result .= nl2br($settings['form_locker_msg_desc']);
                $result .= '<span class="close"></span>';
                $result .= '</div>';
                if($settings['form_locker_hide']=='true') {
                    $result .= '</form>';
                    $result .= '</div>';
                    return $result;
                }
            }
        }


        // @since 3.8.0 - Lock form after specific amount of submissions for logged in user (based on total contact entries created by user)
        if( !empty($settings['user_form_locker']) ) {

            // Let's check if the user is logged in
            $current_user_id = get_current_user_id();
            if( $current_user_id!=0 ) {
                
                // Let's check the total contact entries this user has created for this specific form
                $user_limits = get_post_meta( $form_id, '_super_user_submission_counter', true );
                $count = 0;
                if( !empty($user_limits[$current_user_id]) ) {
                    $count = $user_limits[$current_user_id];
                }
              
                // Let's check if the total amount of entries reaches the limit set for this form
                $limit = 0;
                if( !empty($settings['user_form_locker_limit']) ) {
                    $limit = $settings['user_form_locker_limit'];
                } 

                $display_msg = false;
                if( $count>=$limit ) {
                    $display_msg = true;
                }
                if( !empty($settings['user_form_locker_reset']) ) {
                    // Check if we need to reset the lock counter based on locker reset
                    $last_date = get_post_meta( $form_id, '_super_last_submission_date', true );
                    $reset = $settings['user_form_locker_reset'];
                    switch ($reset) {
                        case 'daily':
                            $current_date = (int)date_i18n('Yz');
                            $last_date = (int)date_i18n('Yz', strtotime($last_date));
                            break;
                        case 'weekly':
                            $current_date = (int)date_i18n('YW');
                            $last_date = (int)date_i18n('YW', strtotime($last_date));
                            break;
                        case 'monthly':
                            $current_date = (int)date_i18n('Yn');
                            $last_date = (int)date_i18n('Yn', strtotime($last_date));
                            break;
                        case 'yearly':
                            $current_date = (int)date_i18n('Y');
                            $last_date = (int)date_i18n('Y', strtotime($last_date));
                            break;
                    }
                    if( $current_date>$last_date ) {
                        // Reset locker
                        delete_post_meta( $form_id, '_super_user_submission_counter' );
                        $display_msg = false;
                    }
                }
                if( $display_msg ) {
                    $result .= '<div class="super-msg super-error">';
                    if(!empty($settings['user_form_locker_msg_title'])) {
                        $result .= '<h1>' . $settings['user_form_locker_msg_title'] . '</h1>';
                    }
                    $result .= nl2br($settings['user_form_locker_msg_desc']);
                    $result .= '<span class="close"></span>';
                    $result .= '</div>';
                    if(!empty($settings['user_form_locker_hide'])) {
                        $result .= '</form>';
                        $result .= '</div>';
                        return $result;
                    }
                }
            }
        }


        // @since 3.2.0 - add honeypot captcha
        $result .= '<input type="text" name="super_hp" size="25" value="" />';

        // @since 3.1.0 - filter to add any HTML before the first form element
        $result = apply_filters( 'super_form_before_first_form_element_filter', $result, array( 'id'=>$form_id, 'settings'=>$settings ) );

        $result .= '<div class="super-shortcode super-field super-hidden">';
        $result .= '<input class="super-shortcode-field" type="hidden" value="' . $form_id . '" name="hidden_form_id" />';
        $result .= '</div>';

        // @since 2.2.0 - update contact entry by ID

        if( (isset( $settings['update_contact_entry'] )) && ($settings['update_contact_entry']=='true') ) {
            $result .= '<div class="super-shortcode super-field super-hidden">';
            $result .= '<input class="super-shortcode-field" type="hidden" value="' . absint($contact_entry_id) . '" name="hidden_contact_entry_id" />';
            $result .= '</div>';
        }

        // Loop through all form elements
        if( !is_array($elements) ) {
            $elements = json_decode( $elements, true );
        }

        if( !empty( $elements ) ) {
            $shortcodes = self::shortcodes();
            // Before doing the actuall loop we need to know how many columns this form contains
            // This way we can make sure to correctly close the column system
            $GLOBALS['super_column_found'] = 0;
            foreach( $elements as $k => $v ) {
                if( $v['tag']=='column' ) $GLOBALS['super_column_found']++;
            }
            foreach( $elements as $k => $v ) {
                if( empty($v['data']) ) $v['data'] = null;
                if( empty($v['inner']) ) $v['inner'] = null;
                $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings, $entry_data );
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
        $result = apply_filters( 'super_form_after_last_form_element_filter', $result, array( 'id'=>$form_id, 'settings'=>$settings ) );

        $result .= '</form>';


        // @since 3.0.0 - new loading method (gif stops/freezes animating when browser is doing javascript at background)
        $result .= '<span class="super-load-icon"></span>';

        $result .= '</div>';

        // @since 1.3   - put styles in global variable and append it to the footer at the very end
        SUPER_Forms()->form_custom_css .= apply_filters( 'super_form_styles_filter', $style_content, array( 'id'=>$form_id, 'settings'=>$settings ) );

        // @since 1.2.8     - Custom CSS per Form
        if( !isset( $settings['form_custom_css'] ) ) $settings['form_custom_css'] = '';
        $settings['form_custom_css'] = stripslashes($settings['form_custom_css']);
        SUPER_Forms()->form_custom_css .= $settings['form_custom_css'];

        // @since 4.2.0 - custom JS script
        if( !empty($global_settings['theme_custom_js']) ) {
            SUPER_Forms()->theme_custom_js = apply_filters( 'super_form_js_filter', $global_settings['theme_custom_js'], array( 'id'=>$form_id, 'settings'=>$settings ) );
        }

        $result = apply_filters( 'super_form_before_do_shortcode_filter', $result, array( 'id'=>$form_id, 'settings'=>$settings ) );
        return do_shortcode( $result );
    }

}

endif;