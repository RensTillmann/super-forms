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
    
    public static $form_fields = array();

    public static function extract($x){
        return shortcode_atts( array( 
            'grid'=>null, 
            'tag'=>'', 
            'group'=>'', 
            'atts'=>array(), 
            'inner'=>array(), 
            'shortcodes'=>null, 
            'settings'=>null, 
            'i18n'=>null, 
            'builder'=>false, 
            'entry_data'=>null, 
            'dynamic'=>0, 
            'dynamic_field_names'=>array(), 
            'inner_field_names'=>array(), 
            'formProgress'=>false,
            'items'=>array(), // used by get_items()
            'prefix'=>'', // used by get_items()
            'data'=>array(), // used by output_builder_html()
            'predefined'=>false // used by output_builder_html()
        ), $x );
    } 

    // @since 4.7.5 - wrapper function to get the value for this field from entry data
    public static function get_entry_data_value($tag, $value, $name, $entry_data){
        if( isset( $entry_data[$name] ) ) {
            if( $tag=='textarea' ) {
                $value = stripslashes( $entry_data[$name]['value'] );
            }else{
                $value = sanitize_text_field( $entry_data[$name]['value'] );
            }
        }
        return $value;
    }

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
         *  HTML Elements
         *
         *  @since      3.5.0
        */
        include( 'shortcodes/html-elements.php' );
        $array = apply_filters( 'super_shortcodes_after_html_elements_filter', $array, $attr );

        $array = apply_filters( 'super_shortcodes_end_filter', $array, $attr );

        self::$shortcodes = $array;

        return $array;
        
    }


    /** 
     *  Merge any possible translations
     *
     *  @since      4.7.0
    */
    public static function merge_i18n($atts, $i18n=null){
        if(!empty($i18n)){
            if( (isset($atts['i18n'])) && (isset($atts['i18n'][$i18n])) ) {
                $atts = array_replace_recursive($atts, $atts['i18n'][$i18n]);
                return $atts;
            }
        }
        if(!empty($_POST['translating'])){
            $i18n = $_POST['i18n'];
            if( (isset($atts['i18n'])) && (isset($atts['i18n'][$i18n])) ) {
                $atts = array_replace_recursive($atts, $atts['i18n'][$i18n]);
                return $atts;
            }
        }
        return $atts;   
    }


    /** 
     *  Get default value
     *
     *  @since      4.9.3
    */
    public static function get_default_value( $tag, $atts, $settings, $entry_data, $default='' ) {
        // Check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = sanitize_text_field( $_POST[$atts['name']] );
        }
        // Get the value for from entry data
        if( !isset( $atts['value'] ) ) $atts['value'] = $default;
        $entry_data_value = self::get_entry_data_value( $tag, $atts['value'], $atts['name'], $entry_data );
        if( (isset($entry_data_value)) && ($entry_data_value!=='') ){
            $atts['value'] = $entry_data_value;
        }
        if($atts['value']!='') $atts['value'] = SUPER_Common::email_tags( $atts['value'], null, $settings, $user=null, $skip=true, $skipSecrets=true );
        // Add shortcode compatibility for default field value
        $atts['value'] = do_shortcode($atts['value']);

        // Required for dropdown field:
        if( $tag=='dropdown' && !empty($atts['absolute_default']) && empty($atts['value']) ) {
            $atts['value'] = $atts['absolute_default'];
        }
        return $atts['value'];
    }


    /** 
     *  Get all items by post_type
     *
     *  @since      1.0.0
    */
    public static function get_item_html($prefix, $tag, $atts, $data_value, $selected_items, $v, $main_image_url){
        if($atts[$prefix.'retrieve_method']=='post_type') {
            $post_id = absint($v['ID']);
            $label_class = array();
            // Check if we want to display a featured image or not
            if($atts['display_featured_image']=='true'){
                $label_class[] = 'super-has-image';
            }
            // If Checkbox
            if( ($tag=='checkbox') && (in_array($data_value, $selected_items, true )) ) {
                $label_class[] = 'super-active super-default-selected';
            }
            // If Radio
            if( ($tag=='radio') && ($atts[$prefix.'value']==$data_value) ) {
                $label_class[] = 'super-active super-default-selected';
            }
            // Create the item (label)
            $item = '<label class="super-item ' . implode(' ', $label_class) . '">';
                // If checkbox or radio
                if($tag=='checkbox' || $tag=='radio'){
                    $item .= '<span class="super-before"><span class="super-after"></span></span>';
                }
                // If Checkbox
                if($tag=='checkbox'){
                    $item .= '<input' . ( !in_array($data_value, $selected_items, true ) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $data_value ) . '" />';
                }
                // If Radio
                if($tag=='radio'){
                    $item .= '<input type="radio" value="' . esc_attr( $data_value ) . '" />';
                }
                // If we need to display the featured image
                if($atts['display_featured_image']=='true'){
                    $item .= '<div class="super-image" style="background-image:url(\'' . $main_image_url . '\');"><img src="' . esc_url($main_image_url) . '"' . '></div>';
                }
                // If we need to display the title, excerpt or price
                $item .= '<span class="super-item-label">';
                    // Shop title (post title)
                    if($atts['display_title']=='true'){
                        $item .= '<span class="super-title"' . SUPER_Common::get_tags_attributes($v['post_title']) . '>' . ($v['post_title']) . '</span>';
                    }
                    // Show excerpt (post/product short description)
                    if($atts['display_excerpt']=='true' && !empty($v['post_excerpt']) ){
                        $item .= '<span class="super-excerpt"' . SUPER_Common::get_tags_attributes($v['post_excerpt']) . '>' . ($v['post_excerpt']) . '</span>';
                    }
                    // Show product price
                    if( function_exists('wc_price') ) {
                        if($atts['display_price']=='true'){
                            $price = get_post_meta( $post_id, '_regular_price', true );
                            // Only if meta exists
                            if(!empty($price)){
                                $sale_price = get_post_meta( $post_id, '_sale_price', true );
                                if(!empty($sale_price)){
                                    $item .= '<span class="super-regular-price super-sale">' . wc_price(get_post_meta( $post_id, '_regular_price', true )) . '</span>';
                                    $item .= '<span class="super-sale-price">' . wc_price(get_post_meta( $post_id, '_sale_price', true )) . '</span>';
                                }else{
                                    $item .= '<span class="super-regular-price">' . wc_price(get_post_meta( $post_id, '_regular_price', true )) . '</span>';
                                }
                            }
                        }
                    }
                $item .= '</span>';
            $item .='</label>';
        }
        return $item;
    }
    public static function get_items($x){
        extract(self::extract($x));

        // When advanced tags is being used get the first value
        if(isset($atts['value']) && $atts['value'] !== '') $real_value = explode(';', $atts['value'])[0];       

        // First retrieve all the values from URL parameter
        $selected_items = array();
        if($tag==='dropdown' || $tag==='checkbox'){
            // When advanced tags is being used get the first value
            if(isset($atts['value']) && $atts['value'] !== '') $selected_items = explode( ",", $atts['value'] );
        }
        if($tag==='radio'){
            if(isset($atts['value']) && $atts['value'] !== '') $selected_items = array($atts['value']);
        }

        // Now get all the actual values (in case user is using dynamic values like: 1;Red)
        $selected_values = array();
        foreach($selected_items as $k => $v){
            // Make sure to trim the values
            $v = trim($v);
            $selected_values[] = explode( ';', $v )[0];
        }

        $items = array();
        
        // dropdown - custom
        // text - autosuggest - custom
        // text - keywords - custom
        // checkbox - custom
        // radio -custom
        if( !isset( $atts[$prefix.'retrieve_method'] ) ) {
            $atts[$prefix.'retrieve_method'] = 'custom';
        } 
        if( $atts[$prefix.'retrieve_method']=='custom' ) {
            // dropdown - custom
            if($tag==='dropdown'){
                $placeholder = array();
                $items = array();
                foreach( $atts['dropdown_items'] as $k => $v ) {
                    // Get advanced tags value
                    $real_value = explode(';', $v['value'])[0];
                    $class = '';
                    // Check if this should be remembered as the default value set via settings
                    if( ($v['checked']==='true' || $v['checked']===true || $v['checked']===1) ) {
                        $class .= 'super-default-selected';
                        $selected_items[] = $v['value'];
                    }
                    if( empty($selected_values) ) {
                        // No URL parameters where set, check if this dropdown has default selected items
                        if( ($v['checked']==='true' || $v['checked']===true || $v['checked']===1) ) {
                            $class .= ' super-active';
                            $placeholder[] = $v['label'];
                        }
                    }else{
                        if(in_array( $real_value, $selected_values, true ) ) {
                            $class .= ' super-active';
                            $placeholder[] = $v['label'];
                        }
                    }
                    $items[] = '<li class="super-item' . ( !empty($class) ? ' ' . $class : '') . '" data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '"' . SUPER_Common::get_tags_attributes($v['value']) . '><div' . SUPER_Common::get_tags_attributes($v['label']) . '>' . ($v['label']) . '</div></li>'; 
                    $items_values[] = $v['value'];
                }
            }
            if($tag==='text'){
                // text - autosuggest - custom
                if( !empty($atts['enable_auto_suggest']) ) {
                    if( ( isset( $atts['autosuggest_items'] ) ) && ( count($atts['autosuggest_items'])!=0 ) && ( $atts['autosuggest_items']!='' ) ) {
                        $items = array();
                        foreach( $atts['autosuggest_items'] as $k => $v ) {
                            if( ($v['checked']==='true' || $v['checked']===true || $v['checked']===1) ) {
                                $selected_items[] = $v['value'];
                                $atts['value'] = $v['value'];
                                $items[] = '<li class="super-item super-active super-default-selected" data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '"' . SUPER_Common::get_tags_attributes($v['value']) . '><div' . SUPER_Common::get_tags_attributes($v['label']) . '>' . ($v['label']) . '</div></li>'; 
                            }else{
                                $items[] = '<li class="super-item' . ($atts['value']==$v['value'] ? ' super-active' : '') . '" data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '"' . SUPER_Common::get_tags_attributes($v['value']) . '><div' . SUPER_Common::get_tags_attributes($v['label']) . '>' . ($v['label']) . '</div></li>'; 
                            }
                            $items_values[] = $v['value'];
                        }
                    }
                }
                // text - keywords - custom
                if( !empty($atts['enable_keywords']) ) {
                    if( ( isset( $atts['keywords_items'] ) ) && ( count($atts['keywords_items'])!=0 ) && ( $atts['keywords_items']!='' ) ) {
                        $items = array();
                        foreach( $atts['keywords_items'] as $k => $v ) {
                            if( ($v['checked']==='true' || $v['checked']===true || $v['checked']===1) ) {
                                $selected_items[] = $v['value'];
                                $item = '<li class="super-item super-active" sfevents="' . esc_attr('{"click":"keywords.add"}') . '" data-value="' . esc_attr($v['value']) . '" data-search-value="' . esc_attr($v['label']) . '"' . SUPER_Common::get_tags_attributes($v['value']) . '>';
                            }else{
                                $item = '<li class="super-item" sfevents="' . esc_attr('{"click":"keywords.add"}') . '" data-value="' . esc_attr($v['value']) . '" data-search-value="' . esc_attr($v['label']) . '"' . SUPER_Common::get_tags_attributes($v['value']) . '>';
                            }
                            $item .= '<span class="super-wp-tag"' . SUPER_Common::get_tags_attributes($v['label']) . '>' . ($v['label']) . '</span>'; 
                            $item .= '</li>';
                            $items[] = $item;
                            $items_values[] = $v['value'];
                        }
                    }
                }
            }
            if($tag==='checkbox'){
                // checkbox - custom
                $items = array();
                foreach( $atts['checkbox_items'] as $k => $v ) {
                    // Get advanced tags value
                    $real_value = explode(';', $v['value'])[0];
                    $class = 'super-item';
                    // Check if this should be remembered as the default value set via settings
                    if( ($v['checked']==='true' || $v['checked']===true || $v['checked']===1) ) {
                        $class .= ' super-default-selected';
                    }
                    if( empty($selected_values) ) {
                        if( ($v['checked']==='true' || $v['checked']===true || $v['checked']===1) ) {
                            $selected_items[] = $v['value'];
                            $class .= ' super-active';
                        }
                    }else{
                        if(in_array( $real_value, $selected_values, true ) ) {
                            $class .= ' super-active';
                        }
                    }
                    if(!empty($atts['class'])) $class .= ' ' . $atts['class'];
                    if( !isset( $v['image'] ) ) $v['image'] = '';
                    if( $v['image']!='' ) {
                        $image = wp_get_attachment_image_src( $v['image'], 'original' );
                        $image = !empty( $image[0] ) ? $image[0] : '';
                        if( !isset( $v['max_width'] ) ) $v['max_width'] = 150;
                        if( !isset( $v['max_height'] ) ) $v['max_height'] = 200;
                        $img_styles = '';
                        if( $v['max_width']!='' ) $img_styles .= 'max-width:' . $v['max_width'] . 'px;';
                        if( $v['max_height']!='' ) $img_styles .= 'max-height:' . $v['max_height'] . 'px;';
                        $class .= ' super-has-image';
                        $item = '<label ' . ( !empty($class) ? 'class="'.$class.'" ' : '') . '>';
                        if( !empty( $image ) ) {
                            $item .= '<div class="super-image" style="background-image:url(\'' . $image . '\');"><img src="' . esc_url($image) . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                        }else{
                            $image = SUPER_PLUGIN_FILE . 'assets/images/image-icon.png';
                            $item .= '<div class="super-image" style="background-image:url(\'' . $image . '\');"><img src="' . esc_url($image) . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                        }
                        $item .= '<input type="checkbox" value="' . esc_attr( $v['value'] ) . '"' . SUPER_Common::get_tags_attributes($v['value']) . ' />';
                        if($v['label']!='') $item .= '<span class="super-item-label"' . SUPER_Common::get_tags_attributes($v['label']) . '>' . $v['label'] . '</span>';
                        $item .='</label>';
                    }else{
                        $item = '<label ' . ( !empty($class) ? 'class="'.$class.'" ' : '') . '>';
                        $item .= '<span class="super-before"><span class="super-after"></span></span>';
                        $item .= '<input type="checkbox" value="' . esc_attr( $v['value'] ) . '"' . SUPER_Common::get_tags_attributes($v['value']) . ' />';
                        $item .= '<div' . SUPER_Common::get_tags_attributes($v['label']) . '>' . $v['label'] . '</div>';
                        $item .= '</label>';
                    }
                    $items[] = $item;
                    $items_values[] = $v['value'];
                }
            }
            if($tag==='radio'){
                // radio -custom
                $items = array();
                $found = false;
                foreach( $atts['radio_items'] as $k => $v ) {

                    // Get advanced tags value
                    $real_value = explode(';', $v['value'])[0];
                    $class = 'super-item';
                    // Check if this should be remembered as the default value set via settings
                    if( ($v['checked']==='true' || $v['checked']===true || $v['checked']===1) ) {
                        $class .= ' super-default-selected';
                    }
                    if( empty($selected_values) ) {
                        if( $found===false && ($v['checked']==='true' || $v['checked']===true || $v['checked']===1) ) {
                            $selected_items[] = $v['value'];
                            $found = true;
                            $class .= ' super-active';
                        }
                    }else{
                        if( $found==false && in_array( $real_value, $selected_values, true ) ) {
                            $found = true;
                            $class .= ' super-active';
                        }
                    }
                    if(!empty($atts['class'])) $class .= ' ' . $atts['class'];
                    if( !isset( $v['image'] ) ) $v['image'] = '';
                    if( $v['image']!='' ) {
                        $image = wp_get_attachment_image_src( $v['image'], 'original' );
                        $image = !empty( $image[0] ) ? $image[0] : '';
                        if( !isset( $v['max_width'] ) ) $v['max_width'] = 150;
                        if( !isset( $v['max_height'] ) ) $v['max_height'] = 200;
                        $img_styles = '';
                        if( $v['max_width']!='' ) $img_styles .= 'max-width:' . $v['max_width'] . 'px;';
                        if( $v['max_height']!='' ) $img_styles .= 'max-height:' . $v['max_height'] . 'px;';
                        $class .= ' super-has-image';
                        $item = '<label ' . ( !empty($class) ? 'class="'.$class.'" ' : '') . '>';
                        if( !empty( $image ) ) {
                            $item .= '<div class="super-image" style="background-image:url(\'' . $image . '\');"><img src="' . esc_url($image) . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                        }else{
                            $image = SUPER_PLUGIN_FILE . 'assets/images/image-icon.png';
                            $item .= '<div class="super-image" style="background-image:url(\'' . $image . '\');"><img src="' . esc_url($image) . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                        }
                        $item .= '<input type="radio" value="' . esc_attr( $v['value'] ) . '"' . SUPER_Common::get_tags_attributes($v['value']) . ' />';
                        if($v['label']!='') $item .= '<span class="super-item-label"' . SUPER_Common::get_tags_attributes($v['label']) . '>' . $v['label'] . '</span>';
                        $item .='</label>';
                    }else{
                        $item = '<label ' . ( !empty($class) ? 'class="'.$class.'" ' : '') . '>';
                        $item .= '<span class="super-before"><span class="super-after"></span></span>';
                        $item .= '<input type="radio" value="' . esc_attr( $v['value'] ) . '"' . SUPER_Common::get_tags_attributes($v['value']) . ' /><div' . SUPER_Common::get_tags_attributes($v['label']) . '>' . $v['label'] . '</div>';
                        $item .= '</label>';
                    }
                    $items[] = $item;
                    $items_values[] = $v['value'];
                }
            }
        }
        
        // dropdown - taxonomy
        // text - autosuggest - taxonomy
        // text - keywords - taxonomy
        // checkbox - taxonomy
        // radio -taxonomy
        if($atts[$prefix.'retrieve_method']=='taxonomy') {
            // dropdown - taxonomy
            if( !isset( $atts[$prefix.'retrieve_method_taxonomy'] ) ) $atts[$prefix.'retrieve_method_taxonomy'] = 'category';
            if( !isset( $atts[$prefix.'retrieve_method_exclude_taxonomy'] ) ) $atts[$prefix.'retrieve_method_exclude_taxonomy'] = '';
            if( !isset( $atts[$prefix.'retrieve_method_hide_empty'] ) ) $atts[$prefix.'retrieve_method_hide_empty'] = 0;
            if( !isset( $atts[$prefix.'retrieve_method_parent'] ) ) $atts[$prefix.'retrieve_method_parent'] = '';
            $args = array(
                'hide_empty' => $atts[$prefix.'retrieve_method_hide_empty'],
                'exclude' => $atts[$prefix.'retrieve_method_exclude_taxonomy'],
                'taxonomy' => $atts[$prefix.'retrieve_method_taxonomy'],
                'parent' => $atts[$prefix.'retrieve_method_parent'],
            );
            $categories = get_categories( $args );
            $items = array();
            foreach( $categories as $v ) {
                if( !isset( $atts[$prefix.'retrieve_method_value'] ) ) $atts[$prefix.'retrieve_method_value'] = 'slug';
                if($atts[$prefix.'retrieve_method_value']=='slug'){
                    $data_value = $v->slug;
                }elseif($atts[$prefix.'retrieve_method_value']=='id'){
                    $data_value = $v->term_id;
                }else{
                    $data_value = $v->name;
                }
                if($tag=='text') {
                    if($prefix=='keywords_'){
                        // text - keywords - taxonomy
                        $items[] = '<li class="super-item" sfevents="' . esc_attr('{"click":"keywords.add"}') . '" data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr($v->name) . '"' . SUPER_Common::get_tags_attributes($data_value) . '><span class="super-wp-tag"' . SUPER_Common::get_tags_attributes($v->name) . '>' . $v->name . '</span></li>';
                    }else{
                        // text - autosuggest - taxonomy
                        $items[] = '<li class="super-item" data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr( $v->name ) . '"' . SUPER_Common::get_tags_attributes($data_value) . '><div' . SUPER_Common::get_tags_attributes($v->name) . '>' . $v->name . '</div></li>'; 
                    }
                }   
                // dropdown - taxonomy
                if($tag=='dropdown')    $items[] = '<li class="super-item" data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr( $v->name ) . '"' . SUPER_Common::get_tags_attributes($data_value) . '><div' . SUPER_Common::get_tags_attributes($v->name) . '>' . $v->name . '</div></li>'; 
                // checkbox - taxonomy
                if($tag=='checkbox')    $items[] = '<label class="super-item' . ( !in_array($data_value, $selected_items, true ) ? '' : ' super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><span class="super-before"><span class="super-after"></span></span><input' . ( !in_array($data_value, $selected_items, true ) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $data_value ) . '"' . SUPER_Common::get_tags_attributes($data_value) . ' /><div' . SUPER_Common::get_tags_attributes($v->name) . '>' . $v->name . '</div></label>';
                // radio - taxonomy
                if($tag=='radio')       $items[] = '<label class="super-item' . ( ($atts['value']!=$data_value) ? '' : ' super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><span class="super-before"><span class="super-after"></span></span><input type="radio" value="' . esc_attr( $data_value ) . '"' . SUPER_Common::get_tags_attributes($data_value) . ' /><div' . SUPER_Common::get_tags_attributes($v->name) . '>' . $v->name . '</div></label>';
                $items_values[] = $data_value;
            }
        }

        // dropdown - post_type
        // checkbox - post_type
        // radio - post_type
        // text - autosuggest - post_type
        // text - keywords - post_type
        if($atts[$prefix.'retrieve_method']=='post_type') {
            if( !isset( $atts[$prefix.'retrieve_method_post'] ) ) $atts[$prefix.'retrieve_method_post'] = 'post';
            if( !isset( $atts[$prefix.'retrieve_method_post_status'] ) ) $atts[$prefix.'retrieve_method_post_status'] = 'publish';
            if( !isset( $atts[$prefix.'retrieve_method_post_limit'] ) ) $atts[$prefix.'retrieve_method_post_limit'] = 30;
            if( !isset( $atts[$prefix.'retrieve_method_exclude_post'] ) ) $atts[$prefix.'retrieve_method_exclude_post'] = '';
            if( !isset( $atts[$prefix.'retrieve_method_parent'] ) ) $atts[$prefix.'retrieve_method_parent'] = '';
            if( !isset( $atts[$prefix.'retrieve_method_orderby'] ) ) $atts[$prefix.'retrieve_method_orderby'] = 'title';
            if( !isset( $atts[$prefix.'retrieve_method_order'] ) ) $atts[$prefix.'retrieve_method_order'] = 'asc';
            $atts[$prefix.'retrieve_method_order'] = strtolower($atts[$prefix.'retrieve_method_order']);
            $args = array(
                'post_type' => $atts[$prefix.'retrieve_method_post'],
                'post_status' => $atts[$prefix.'retrieve_method_post_status'],
                'exclude' => $atts[$prefix.'retrieve_method_exclude_post'],
                'post_parent' => $atts[$prefix.'retrieve_method_parent'],
                'orderby' => $atts[$prefix.'retrieve_method_orderby'],
                'order' => $atts[$prefix.'retrieve_method_order'],
                'numberposts' => (int)$atts[$prefix.'retrieve_method_post_limit']
            );
            if($atts[$prefix.'retrieve_method_orderby']=='price'){
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_price';
            }

            // Check if we need to do an advanced filter based on taxonomy
            if(!empty($atts[$prefix.'retrieve_method_filters'])){
                // Make sure we grab the tag ID and then add it to the array
                $filters = explode("\n", $atts[$prefix.'retrieve_method_filters']);
                $tax_query = array(
                    'relation' => (!empty($atts[$prefix.'retrieve_method_filter_relation']) ? $atts[$prefix.'retrieve_method_filter_relation'] : 'IN')
                );
                foreach($filters as $fv){
                    $params = explode("|", $fv);
                    if(isset($params[0]) && isset($params[1]) && isset($params[2])) {
                        $field = $params[0];
                        $value = $params[1];
                        $taxonomy = $params[2];
                        $operator = (!empty($params[3]) ? $params[3] : 'IN');
                        $tax_query[] = array(
                            'operator' => $operator,
                            'taxonomy' => $taxonomy,
                            'field' => $field,
                            'terms' => explode(",",$value)
                        );
                    } 
                }
                if(count($tax_query)>1){
                    $args['tax_query'] = $tax_query;
                }
            }
            $posts = get_posts( $args );
            $items = array();
            foreach( $posts as $v ) {
                $v = (array) $v;
                // Try to grab the main featured image
                // In case the variable product does not have any images, we will use this main image as a fallback
                $image = wp_get_attachment_image_src(get_post_thumbnail_id($v['ID']));
                $image_url = !empty( $image[0] ) ? $image[0] : '';
                if( !empty( $image_url ) ) {
                    // If exists
                    $main_featured_image_url = $image_url;
                }else{
                    // If doesn't exists use default placeholder image
                    $main_featured_image_url = SUPER_PLUGIN_FILE . 'assets/images/image-icon.png';
                }
                $final_featured_image_url = $main_featured_image_url;


                // Find out wether this is a WooCommerce product and if it's a variable product
                // If so we must loop through all the variations and display them, because each variation will have it's own price and or meta data
                if( ($atts[$prefix.'retrieve_method_post']==='product') && (class_exists('WooCommerce')) && (wc_get_product( $v['ID'] )->get_type()==='variable') ) {
                    // Seems like we got a variable product here
                    // Get all variations based of this product ID
                    $product = wc_get_product( $v['ID'] );
                    $available_variations = array();
                    foreach ( $product->get_children() as $child_id ) {
                        $variation = wc_get_product( $child_id );
                        // Hide out of stock variations if 'Hide out of stock items from the catalog' is checked.
                        if ( ! $variation || ! $variation->exists() || ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $variation->is_in_stock() ) ) {
                            continue;
                        }
                        // Filter 'woocommerce_hide_invisible_variations' to optionally hide invisible variations (disabled variations and variations with empty price).
                        if ( apply_filters( 'woocommerce_hide_invisible_variations', true, $product->get_id(), $variation ) && ! $variation->variation_is_visible() ) {
                            continue;
                        }
                        $array = $product->get_available_variation( $variation );
                        $array['ID'] = $child_id;
                        $array['post_title'] = $variation->get_name();
                        $available_variations[] = $array;
                    }
                    $available_variations = array_values( array_filter( $available_variations ) );
                    foreach($available_variations as $vk => $vv){
                        // Try to get the featured image specifically for this attribute
                        $image = wp_get_attachment_image_src(get_post_thumbnail_id($vv['ID']));
                        $image_url = !empty( $image[0] ) ? $image[0] : '';
                        if( !empty( $image_url ) ) {
                            $final_featured_image_url = $image_url;
                        }else{
                            $final_featured_image_url = $main_featured_image_url;
                        }

                        // @since 1.2.5
                        if( !isset( $atts[$prefix.'retrieve_method_value'] ) ) $atts[$prefix.'retrieve_method_value'] = 'slug';
                        if($atts[$prefix.'retrieve_method_value']=='slug'){
                            $data_value = $v['post_name'];
                        }elseif($atts[$prefix.'retrieve_method_value']=='id'){
                            $data_value = $vv['variation_id'];
                        }elseif($atts[$prefix.'retrieve_method_value']=='custom'){
                            $data_value = '';
                            $retrieve_method_meta_keys = explode("\n", $atts[$prefix.'retrieve_method_meta_keys']);
                            foreach($retrieve_method_meta_keys as $rk => $rv){
                                if($rv=='featured_image'){
                                    $attachment_image = wp_get_attachment_image_src(get_post_thumbnail_id($v['ID']));
                                    if($attachment_image){
                                        $image_url = $attachment_image[0];
                                        if($rk>0){
                                            $data_value .= ';'.$image_url;
                                        }else{
                                            $data_value .= $image_url;
                                        }
                                    }
                                    continue;
                                }
                                if($rk>0){
                                    if(isset($vv[$rv])){
                                        $data_value .= ';'.$vv[$rv];
                                    }else{
                                        // Get post meta data
                                        $meta_value = get_post_meta( $vv['ID'], $rv, true );
                                        if(!is_array($meta_value)){
                                            $data_value .= ';'.$meta_value;
                                        }else{
                                            $data_value .= ';Array()';
                                        }
                                    }
                                }else{
                                    if(isset($vv[$rv])){
                                        $data_value .= $vv[$rv];
                                    }else{
                                        // Get post meta data
                                        $meta_value = get_post_meta( $vv['ID'], $rv, true );
                                        if(!is_array($meta_value)){
                                            $data_value .= $meta_value;
                                        }else{
                                            $data_value .= 'Array()';
                                        }
                                    }
                                }
                            }
                        }else{
                            $data_value = $vv['post_title'];
                        }
                        $sku = '';
                        if(function_exists('wc_get_product')){
                            $product = wc_get_product( $vv['ID'] );
                            $sku = ';'.$product->get_sku();    
                        }
                        if($tag=='text') {
                            if($prefix=='keywords_'){
                                $items[] = '<li class="super-item" sfevents="' . esc_attr('{"click":"keywords.add"}') . '" data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr( $vv['post_title']) . $sku . '"' . SUPER_Common::get_tags_attributes($data_value) . '><span class="super-wp-tag"' . SUPER_Common::get_tags_attributes($vv['post_title']) . '>' . $vv['post_title'] . '</span></li>';
                            }else{
                                $items[] = '<li class="super-item" data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr( $vv['post_title'] ) . $sku . '"' . SUPER_Common::get_tags_attributes($data_value) . '><div' . SUPER_Common::get_tags_attributes($vv['post_title']) . '>' . $vv['post_title'] . '</div></li>'; 
                            }
                        }   
                        if($tag=='dropdown')    $items[] = '<li class="super-item"  data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr( $vv['post_title'] ) . $sku . '"' . SUPER_Common::get_tags_attributes($data_value) . '><div' . SUPER_Common::get_tags_attributes($vv['post_title']) . '>' . $vv['post_title'] . '</div></li>'; 
                        if($tag=='checkbox'){
                            $items[] = self::get_item_html($prefix, $tag, $atts, $data_value, $selected_items, $vv, $final_featured_image_url);
                        }
                        if($tag=='radio'){
                            $items[] = self::get_item_html($prefix, $tag, $atts, $data_value, null, $vv, $final_featured_image_url);
                        }
                        $items_values[] = $data_value;
                    }
                }else{
                    // @since 1.2.5
                    if( !isset( $atts[$prefix.'retrieve_method_value'] ) ) $atts[$prefix.'retrieve_method_value'] = 'slug';
                    if($atts[$prefix.'retrieve_method_value']=='slug'){
                        $data_value = $v['post_name'];
                    }elseif($atts[$prefix.'retrieve_method_value']=='id'){
                        $data_value = $v['ID'];
                    }elseif($atts[$prefix.'retrieve_method_value']=='custom'){
                        $data_value = '';
                        $retrieve_method_meta_keys = explode("\n", $atts[$prefix.'retrieve_method_meta_keys']);
                        foreach($retrieve_method_meta_keys as $rk => $rv){
                            if($rv=='featured_image'){
                                $attachment_image = wp_get_attachment_image_src(get_post_thumbnail_id($v['ID']));
                                if($attachment_image){
                                    $image_url = $attachment_image[0];
                                    if($rk>0){
                                        $data_value .= ';'.$image_url;
                                    }else{
                                        $data_value .= $image_url;
                                    }
                                }
                                continue;
                            }
                            if($rk>0){
                                if(isset($v[$rv])){
                                    $data_value .= ';'.$v[$rv];
                                }else{
                                    // Get post meta data
                                    $meta_value = get_post_meta( $v['ID'], $rv, true );
                                    if(!is_array($meta_value)){
                                        $data_value .= ';'.$meta_value;
                                    }else{
                                        $data_value .= ';Array()';
                                    }
                                }
                            }else{
                                if(isset($v[$rv])){
                                    $data_value .= $v[$rv];
                                }else{
                                    // Get post meta data
                                    $meta_value = get_post_meta( $v['ID'], $rv, true );
                                    if(!is_array($meta_value)){
                                        $data_value .= $meta_value;
                                    }else{
                                        $data_value .= 'Array()';
                                    }
                                }
                            }
                        }
                    }else{
                        $data_value = $v['post_title'];
                    }
                    $item_value = $data_value;
                    if($tag=='text') {
                        if($prefix=='keywords_'){
                            $items[] = '<li class="super-item" sfevents="' . esc_attr('{"click":"keywords.add"}') . '" data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr( $v['post_title']) . '"' . SUPER_Common::get_tags_attributes($data_value) . '><span class="super-wp-tag"' . SUPER_Common::get_tags_attributes($v['post_title']) . '>' . ($v['post_title']) . '</span></li>';
                        }else{
                            $items[] = '<li class="super-item ' . ( $atts['value']==explode(';', $data_value)[0] ? ' super-active' : '' ) . '" data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr( $v['post_title']) . '"' . SUPER_Common::get_tags_attributes($data_value) . '><div' . SUPER_Common::get_tags_attributes($v['post_title']) . '>' . ($v['post_title']) . '</div></li>';
                            $item_value = explode(';', $data_value)[0];
                        }
                    }
                    if($tag=='dropdown')    $items[] = '<li class="super-item" data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr( $v['post_title']) . '"' . SUPER_Common::get_tags_attributes($data_value) . '><div' . SUPER_Common::get_tags_attributes($v['post_title']) . '>' . ($v['post_title']) . '</div></li>'; 
                    if($tag=='checkbox'){
                        $items[] = self::get_item_html($prefix, $tag, $atts, $data_value, $selected_items, $v, $main_featured_image_url);
                    }
                    if($tag=='radio'){
                        $items[] = self::get_item_html($prefix, $tag, $atts, $data_value, $selected_items, $v, $main_featured_image_url);
                    }
                    $items_values[] = $item_value;
                }
            }
        }

        // dropdown - product_attribute
        // checkbox - product_attribute
        // radio - product_attribute
        // text - autosuggest - product_attribute
        // text - keywords - product_attribute
        if($atts[$prefix.'retrieve_method']=='product_attribute') {
            if( !isset( $atts[$prefix.'retrieve_method_product_attribute'] ) ) $atts[$prefix.'retrieve_method_product_attribute'] = '';
            if($atts[$prefix.'retrieve_method_product_attribute']!=''){
                // Let's try to retrieve product attributes
                if ( class_exists( 'WooCommerce' ) ) {
                    global $post;
                    if( isset( $post ) ) {
                        global $product;
                        $attributes = $product->get_attribute( $atts[$prefix.'retrieve_method_product_attribute'] );
                        $attributes = explode(', ', $attributes);
                        $items = array();
                        foreach( $attributes as $v ) {
                            if($tag=='text') {
                                if($prefix=='keywords_'){
                                    $items[] = '<li class="super-item" sfevents="' . esc_attr('{"click":"keywords.add"}') . '" data-value="' . esc_attr( $v ) . '" data-search-value="' . esc_attr( $v ) . '"' . SUPER_Common::get_tags_attributes($v) . '><span class="super-wp-tag"' . SUPER_Common::get_tags_attributes($v) . '>' . $v . '</span></li>'; 
                                }else{
                                    $items[] = '<li class="super-item" data-value="' . esc_attr( $v ) . '" data-search-value="' . esc_attr( $v ) . '"' . SUPER_Common::get_tags_attributes($v) . '><div' . SUPER_Common::get_tags_attributes($v) . '>' . $v . '</div></li>'; 
                                }
                            }
                            if($tag=='dropdown')    $items[] = '<li class="super-item" data-value="' . esc_attr( $v ) . '" data-search-value="' . esc_attr( $v ) . '"' . SUPER_Common::get_tags_attributes($v) . '><div' . SUPER_Common::get_tags_attributes($v) . '>' . $v . '</div></li>';  
                            if($tag=='checkbox')    $items[] = '<label class="super-item' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><span class="super-before"><span class="super-after"></span></span><input type="checkbox" value="' . esc_attr( $v ) . '"' . SUPER_Common::get_tags_attributes($v) . ' /><div' . SUPER_Common::get_tags_attributes($v) . '>' . $v . '</div></label>';
                            if($tag=='radio')       $items[] = '<label class="super-item' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><span class="super-before"><span class="super-after"></span></span><input type="radio" value="' . esc_attr( $v ) . '"' . SUPER_Common::get_tags_attributes($v) . ' /><div' . SUPER_Common::get_tags_attributes($v) . '>' . $v . '</div></label>';
                            $items_values[] = $v;
                        }
                    }
                }          
            }
        }

        // dropdown - tags
        // checkbox - tags
        // radio - tags
        // text - autosuggest - tags
        // text - keywords - tags
        if($atts[$prefix.'retrieve_method']=='tags') {
            $tags = get_tags(
                array(
                    'hide_empty'=>false
                )
            );
            $items = array();
            foreach ( $tags as $v ) {
                if( !isset( $atts[$prefix.'retrieve_method_value'] ) ) $atts[$prefix.'retrieve_method_value'] = 'slug';
                if( $atts[$prefix.'retrieve_method_value']=='slug' ) {
                    $data_value = $v->slug;
                }elseif( $atts[$prefix.'retrieve_method_value']=='id' ) {
                    $data_value = $v->term_id;
                }else{
                    $data_value = $v->name;
                }
                if($tag=='text') {
                    if($prefix=='keywords_'){
                        $item = '<li class="super-item" sfevents="' . esc_attr('{"click":"keywords.add"}') . '" data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr($v->name) . '"' . SUPER_Common::get_tags_attributes($data_value) . '>';
                        $item .= '<span class="super-wp-tag"' . SUPER_Common::get_tags_attributes($v->name) . '>' . $v->name . '</span>'; 
                        $item .= '<span class="super-wp-tag-count">×&nbsp;' . $v->count . '</span>'; 
                        if( !empty($v->description) ) {
                            $item .= '<span class="super-flex-clear"></span>';
                            $item .= '<span class="super-wp-tag-desc"' . SUPER_Common::get_tags_attributes($v->description) . '>' . $v->description . '</span>'; 
                        }
                        $item .= '</li>';
                        $items[] = $item;
                    }else{
                        $items[] = '<li class="super-item" data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr( $v->name ) . '"' . SUPER_Common::get_tags_attributes($data_value) . '><div' . SUPER_Common::get_tags_attributes($v->name) . '>' . $v->name . '</div></li>';
                    }
                }
                if($tag=='dropdown')    $items[] = '<li class="super-item" data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr( $v->name ) . '"' . SUPER_Common::get_tags_attributes($data_value) . '><div' . SUPER_Common::get_tags_attributes($v->name) . '>' . $v->name . '</div></li>';  
                if($tag=='checkbox')    $items[] = '<label class="super-item' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><span class="super-before"><span class="super-after"></span></span><input type="checkbox" value="' . esc_attr( $data_value ) . '"' . SUPER_Common::get_tags_attributes($data_value) . ' /><div' . SUPER_Common::get_tags_attributes($v->name) . '>' . $v->name . '</div></label>';
                if($tag=='radio')       $items[] = '<label class="super-item' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><span class="super-before"><span class="super-after"></span></span><input type="radio" value="' . esc_attr( $data_value ) . '"' . SUPER_Common::get_tags_attributes($data_value) . ' /><div' . SUPER_Common::get_tags_attributes($v->name) . '>' . $v->name . '</div></label>';
                $items_values[] = $data_value;
            }
        }

        // dropdown - csv
        // checkbox - csv
        // radio - csv
        // text - autosuggest - csv
        // text - keywords - csv
        if($atts[$prefix.'retrieve_method']=='csv') {
            $delimiter = ',';
            $enclosure = '"';
            if( isset( $atts[$prefix.'retrieve_method_delimiter'] ) ) $delimiter = $atts[$prefix.'retrieve_method_delimiter'];
            if( isset( $atts[$prefix.'retrieve_method_enclosure'] ) ) $enclosure = stripslashes($atts[$prefix.'retrieve_method_enclosure']);
            $file = get_attached_file($atts[$prefix.'retrieve_method_csv']);
            if( (!empty($file)) && (($handle = fopen($file, "r")) !== FALSE) ) {
                // Progress file pointer and get first 3 characters to compare to the BOM string.
                $bom = "\xef\xbb\xbf"; // BOM as a string for comparison.
                if (fgets($handle, 4) !== $bom) rewind($handle); // BOM not found - rewind pointer to start of file.
                $placeholder = array();
                $items = array();
                while (($data = fgetcsv($handle, 10000, $delimiter, $enclosure)) !== FALSE) {
                    $num = count($data);
                    $value = 'undefined';
                    $title = 'undefined';
                    for ( $c=0; $c < $num; $c++ ) {
                        if( $c==0) $value = $data[$c];
                        if( $c==1 ) $title = $data[$c];
                    }
                    if( $title=='undefined' ) {
                        $title = $value; 
                    }
                    if($tag=='text') {
                        // text - autosuggest - csv
                        if( !empty($atts['enable_auto_suggest']) ) {
                            $items[] = '<li class="super-item' . ($atts['value']==$value ? ' super-active' : '') . '" data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $title ) . '"' . SUPER_Common::get_tags_attributes($value) . '><div' . SUPER_Common::get_tags_attributes($title) . '>' . $title . '</div></li>';
                        }else{
                            // text - keywords - csv
                            if($prefix=='keywords_'){
                                $item = '<li class="super-item" sfevents="' . esc_attr('{"click":"keywords.add"}') . '" data-value="' . esc_attr($value) . '" data-search-value="' . esc_attr($title) . '"' . SUPER_Common::get_tags_attributes($value) . '>';
                                $item .= '<span class="super-wp-tag"' . SUPER_Common::get_tags_attributes($title) . '>' . $title . '</span>'; 
                                $item .= '</li>';
                                $items[] = $item;
                            }else{
                                $items[] = '<li class="super-item" data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $title ) . '"' . SUPER_Common::get_tags_attributes($value) . '><div' . SUPER_Common::get_tags_attributes($title) . '>' . $title . '</div></li>';
                            }
                        }    
                    }
                    if($tag=='dropdown') {
                        // Get advanced tags value
                        $real_value = explode(';', $value)[0];
                        $class = '';
                        if(in_array( $real_value, $selected_values, true ) ) {
                            $class .= ' super-active';
                            $placeholder[] = $title;
                        }
                        $items[] = '<li class="super-item' . (!empty($class) ? ' ' . $class : '' ) . '" data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $title ) . '"' . SUPER_Common::get_tags_attributes($value) . '><div' . SUPER_Common::get_tags_attributes($title) . '>' . $title . '</div></li>';
                    }
                    if($tag=='checkbox') {
                        $items[] = '<label class="super-item' . ( !in_array($value, $selected_values, true ) ? '' : 'super-active') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><span class="super-before"><span class="super-after"></span></span><input' . ( !in_array( $value, $selected_items, true ) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $value ) . '' . SUPER_Common::get_tags_attributes($value) . '" /><div' . SUPER_Common::get_tags_attributes($title) . '>' . $title . '</div></label>';
                    }
                    if($tag=='radio') {
                        $items[] = '<label class="super-item' . ( ($atts['value']!=$value) ? '' : 'super-active') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><span class="super-before"><span class="super-after"></span></span><input type="radio" value="' . esc_attr( $value ) . '"' . SUPER_Common::get_tags_attributes($value) . ' /><div' . SUPER_Common::get_tags_attributes($title) . '>' . $title . '</div></label>';
                    }
                    $items_values[] = $value;
                }
                fclose($handle);
            }
        }

        // dropdown - author
        // checkbox - author
        // radio - author
        // text - autosuggest - author
        // text - keywords - author
        if( ($atts[$prefix.'retrieve_method']=='author') || ($atts[$prefix.'retrieve_method']=='post_meta') ) {
            $meta_field_name = '';
            $line_explode = "\n";
            $option_explode = '|';
            if( !empty( $atts[$prefix.'retrieve_method_author_field'] ) ) $meta_field_name = $atts[$prefix.'retrieve_method_author_field'];
            if( !empty( $atts[$prefix.'retrieve_method_author_line_explode'] ) ) $line_explode = $atts[$prefix.'retrieve_method_author_line_explode'];
            if( !empty( $atts[$prefix.'retrieve_method_author_option_explode'] ) ) $option_explode = $atts[$prefix.'retrieve_method_author_option_explode'];

            // Retrieve meta data from author
            if( $atts[$prefix.'retrieve_method']=='author' ) {
                // First check if we are on the author profile page, and see if we can find author based on slug
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
            }
            
            // Retrieve meta data from post
            if( $atts[$prefix.'retrieve_method']=='post_meta' ) {
                global $post;
                $data = get_post_meta( $post->ID, $meta_field_name, true ); 
            }

            // If data exists return in list
            if( $data ) {
                $data = explode( $line_explode, $data );
                if( is_array($data) ) {
                    $items = array();
                    foreach( $data as $v ) {
                        $values = explode( $option_explode , $v );
                        $label = ( isset( $values[0] ) ? $values[0] : '' );
                        $value = ( isset( $values[1] ) ? $values[1] : $label );
                        if( empty($label) ) continue;

                        // @since 4.0.2 - remove line breaks for dropdowns
                        $label = str_replace(array("\r", "\n"), '', $label);
                        $value = str_replace(array("\r", "\n"), '', $value);

                        if($tag=='text') {
                            if($prefix=='keywords_'){
                                $item = '<li class="super-item" sfevents="' . esc_attr('{"click":"keywords.add"}') . '" data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $label ) . '"' . SUPER_Common::get_tags_attributes($value) . '>';
                                $item .= '<span class="super-wp-tag"' . SUPER_Common::get_tags_attributes($label) . '>' . $label . '</span>'; 
                                $item .= '</li>';
                                $items[] = $item;
                            }else{
                                $items[] = '<li class="super-item" data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $label ) . '"' . SUPER_Common::get_tags_attributes($value) . '><div' . SUPER_Common::get_tags_attributes($label) . '>' . $label . '</div></li>';
                            }
                        }
                        if($tag=='dropdown')    $items[] = '<li class="super-item" data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $label ) . '"' . SUPER_Common::get_tags_attributes($value) . '><div' . SUPER_Common::get_tags_attributes($label) . '>' . $label . '</div></li>';
                        if($tag=='checkbox')    $items[] = '<label class="super-item' . ( !in_array($value, $selected_items, true ) ? '' : ' super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><span class="super-before"><span class="super-after"></span></span><input' . ( !in_array($value, $selected_items, true ) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $value ) . '"' . SUPER_Common::get_tags_attributes($value) . ' /><div' . SUPER_Common::get_tags_attributes($label) . '>' . $label . '</div></label>';
                        if($tag=='radio')       $items[] = '<label class="super-item' . ( ($atts['value']!=$value) ? '' : ' super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><span class="super-before"><span class="super-after"></span></span><input type="radio" value="' . esc_attr( $value ) . '"' . SUPER_Common::get_tags_attributes($value) . ' /><div' . SUPER_Common::get_tags_attributes($label) . '>' . $label . '</div></label>';
                        $items_values[] = $value;
                    }
                }
            }
        }
        
        // dropdown - post_terms
        // checkbox - post_terms
        // radio - post_terms
        // text - autosuggest - post_terms
        // text - keywords - post_terms
        if($atts[$prefix.'retrieve_method']=='post_terms') {
            if( empty( $atts[$prefix.'retrieve_method_taxonomy'] ) ) $atts[$prefix.'retrieve_method_taxonomy'] = 'category';
            if( empty( $atts[$prefix.'retrieve_method_post_terms_label'] ) ) $atts[$prefix.'retrieve_method_post_terms_label'] = 'names';
            if( empty( $atts[$prefix.'retrieve_method_post_terms_value'] ) ) $atts[$prefix.'retrieve_method_post_terms_value'] = 'slugs';
            $args = array(
                'taxonomy' => $atts[$prefix.'retrieve_method_taxonomy'],
                'terms_label' => $atts[$prefix.'retrieve_method_post_terms_label'],
                'terms_value' => $atts[$prefix.'retrieve_method_post_terms_value'],
            );
            // We possibly are looking for post terms (taxonomy)
            global $post;
            if( isset( $post ) ) {
                $terms = wp_get_post_terms( $post->ID, $args['taxonomy'], array( 'fields' => 'all' ) );
                $items = array();
                foreach( $terms as $v ) {
                    $data_label = $v->name;
                    $data_value = $v->slug;
                    if($atts[$prefix.'retrieve_method_post_terms_label']=='slugs') $data_label = $v->slug;
                    if($atts[$prefix.'retrieve_method_post_terms_label']=='ids') $data_label = $v->term_id;
                    if($atts[$prefix.'retrieve_method_post_terms_value']=='names') $data_value = $v->name;
                    if($atts[$prefix.'retrieve_method_post_terms_value']=='ids') $data_value = $v->term_id;
                    if($tag=='text') {
                        if($prefix=='keywords_'){
                            // text - keywords - taxonomy
                            $items[] = '<li class="super-item" sfevents="' . esc_attr('{"click":"keywords.add"}') . '" data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr($data_label) . '"' . SUPER_Common::get_tags_attributes($data_value) . '><span class="super-wp-tag"' . SUPER_Common::get_tags_attributes($data_label) . '>' . $data_label . '</span></li>';
                        }else{
                            // text - autosuggest - taxonomy
                            $items[] = '<li class="super-item" data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr( $data_label ) . '"' . SUPER_Common::get_tags_attributes($data_value) . '><div' . SUPER_Common::get_tags_attributes($data_label) . '>' . $data_label . '</div></li>'; 
                        }
                    }   
                    // dropdown - taxonomy
                    if($tag=='dropdown')    $items[] = '<li class="super-item" data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr( $data_label ) . '"' . SUPER_Common::get_tags_attributes($data_value) . '><div' . SUPER_Common::get_tags_attributes($data_label) . '>' . $data_label . '</div></li>'; 
                    // checkbox - taxonomy
                    if($tag=='checkbox')    $items[] = '<label class="super-item' . ( !in_array($data_value, $selected_items, true ) ? '' : ' super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><span class="super-before"><span class="super-after"></span></span><input' . ( !in_array($data_value, $selected_items, true ) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $data_value ) . '"' . SUPER_Common::get_tags_attributes($data_value) . ' /><div' . SUPER_Common::get_tags_attributes($data_label) . '>' . $data_label . '</div></label>';
                    // radio - taxonomy
                    if($tag=='radio')       $items[] = '<label class="super-item' . ( ($atts['value']!=$data_value) ? '' : ' super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><span class="super-before"><span class="super-after"></span></span><input type="radio" value="' . esc_attr( $data_value ) . '"' . SUPER_Common::get_tags_attributes($data_value) . ' /><div' . SUPER_Common::get_tags_attributes($data_label) . '>' . $data_label . '</div></label>';
                    $items_values[] = $data_value;
                }
            }
        }

        // dropdown - author
        // checkbox - author
        // radio - author
        // text - autosuggest - author
        // text - keywords - author
        if($atts[$prefix.'retrieve_method']=='users') {
            $exclude_users = array();
            $role_filters = array();
            $default_user_label = '#{ID} - {first_name} {last_name} ({user_email})';
            $meta_keys = 'ID';
            if( !empty( $atts[$prefix.'retrieve_method_exclude_users'] ) ) $exclude_users = explode(",",$atts[$prefix.'retrieve_method_exclude_users']);
            if( !empty( $atts[$prefix.'retrieve_method_role_filters'] ) ) $role_filters = explode("\n",$atts[$prefix.'retrieve_method_role_filters']);
            if( !empty( $atts[$prefix.'retrieve_method_user_label'] ) ) $default_user_label = $atts[$prefix.'retrieve_method_user_label'];
            if( !empty( $atts[$prefix.'retrieve_method_user_meta_keys'] ) ) $meta_keys = $atts[$prefix.'retrieve_method_user_meta_keys'];
            foreach($role_filters as $k => $v){
                $role_filters[$k] = trim($v);    
            }
            $args = array(
                'role__in'     => $role_filters,
                'orderby'      => 'login',
                'order'        => 'ASC',
                'exclude'      => $exclude_users
            ); 
            $users = (array) get_users( $args );
            $regex = '/\{(.*?)\}/';
            $users_array = array();
            foreach($users as $k => $v){
                $v = (array) $v->data;
                // Replace all {tags} and build the user label
                $user_label = $default_user_label;
                preg_match_all($regex, $user_label, $matches, PREG_SET_ORDER, 0);
                foreach($matches as $mk => $mv){
                    if( empty($mv[1]) ) continue;
                    if( isset($mv[1]) && isset($v[$mv[1]]) ) {
                        $user_label = str_replace( '{' . $mv[1] . '}', $v[$mv[1]], $user_label );
                    }else{
                        // Maybe we need to search in user meta data
                        $meta_value = get_user_meta( $v['ID'], $mv[1], true );
                        $user_label = str_replace( '{' . $mv[1] . '}', $meta_value, $user_label );
                    }
                }
                // Replace all meta_keys and build the user value
                $mk = explode("\n", $meta_keys);
                $user_value = array();
                foreach($mk as $mv){
                    if( empty($mv) ) continue;
                    if( isset($v[$mv]) ) {
                        $user_value[] = $v[$mv];
                    }else{
                        // Maybe we need to search in user meta data
                        $meta_value = get_user_meta( $v['ID'], $mv, true );
                        $user_value[] = $meta_value;
                    }   
                }
                $users_array[] = array(
                    'label' => $user_label,
                    'value' => implode(';', $user_value)
                );
            }

            if(isset($entry_data[$atts['name']])) {
                $first_entry_value = explode(";", $entry_data[$atts['name']]['value'])[0];
            }
            $items = array();
            foreach($users_array as $k => $v){
                $value = $v['value'];
                $label = $v['label'];
                $active = false;
                if( (!empty($atts['value'])) && ($atts['value']==explode(";",$value)[0]) ){
                    $active = true;
                }
                $selected = false;
                if(isset($entry_data[$atts['name']])) {
                    $first_item_value = explode(";", $value)[0];
                    if($first_entry_value==$first_item_value){
                        $selected = true;
                        $atts['value'] = $label;
                    }
                }
                if($active){
                    $atts['value'] = esc_attr( $label );
                }
                if($tag=='text') {
                    if($prefix=='keywords_'){
                        $item = '<li class="super-item" sfevents="' . esc_attr('{"click":"keywords.add"}') . '" data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $label ) . '"' . SUPER_Common::get_tags_attributes($value) . '>';
                        $item .= '<span class="super-wp-tag"' . SUPER_Common::get_tags_attributes($label) . '>' . $label . '</span>'; 
                        $item .= '</li>';
                        $items[] = $item;
                    }else{
                        $items[] = '<li class="super-item' . ($selected || $active ? ' super-active' : '') . '" data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $label ) . '"' . SUPER_Common::get_tags_attributes($value) . '><div' . SUPER_Common::get_tags_attributes($label) . '>' . $label . '</div></li>';
                    }
                }
                if($tag=='dropdown'){
                    $items[] = '<li class="super-item' . ($selected || $active ? ' super-active' : '') . '" data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $label ) . '"' . SUPER_Common::get_tags_attributes($value) . '><div' . SUPER_Common::get_tags_attributes($label) . '>' . $label . '</div></li>';
                }
                if($tag=='checkbox'){
                    $items[] = '<label class="super-item' . ( !in_array($value, $selected_items, true ) || $active ? '' : ' super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><span class="super-before"><span class="super-after"></span></span><input' . ( !in_array($value, $selected_items, true ) || $active ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $value ) . '"' . SUPER_Common::get_tags_attributes($value) . ' /><div' . SUPER_Common::get_tags_attributes($label) . '>' . $label . '</div></label>';
                }
                if($tag=='radio'){
                    $items[] = '<label class="super-item' . ( $selected || $active ? ' super-active super-default-selected' : '') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><span class="super-before"><span class="super-after"></span></span><input type="radio" value="' . esc_attr( $value ) . '"' . SUPER_Common::get_tags_attributes($value) . ' /><div' . SUPER_Common::get_tags_attributes($label) . '>' . $label . '</div></label>';
                }
                $items_values[] = $value;
            }
        }


        // dropdown - db_table
        // checkbox - db_table
        // radio - db_table
        // text - autosuggest - db_table
        // text - keywords - db_table
        if($atts[$prefix.'retrieve_method']=='db_table') {
            if( !isset( $atts[$prefix.'retrieve_method_db_table'] ) ) $atts[$prefix.'retrieve_method_db_table'] = '';
            if( !isset( $atts[$prefix.'retrieve_method_db_row_value'] ) ) $atts[$prefix.'retrieve_method_db_row_value'] = '';
            if( !isset( $atts[$prefix.'retrieve_method_db_row_label'] ) ) $atts[$prefix.'retrieve_method_db_row_label'] = '';
            $column_value = $atts[$prefix.'retrieve_method_db_row_value'];
            $column_label = $atts[$prefix.'retrieve_method_db_row_label']; // Example: '[{code}] - {last_name} {initials} - [{date}] - {email}';

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
            $table = $atts[$prefix.'retrieve_method_db_table'];
            $results = $wpdb->get_results("SELECT $select_query FROM $table WHERE 1=1", ARRAY_A);
            $items = array();
            foreach( $results as $k => $v ) {
                $final_value = $column_value;
                $final_label = $column_label;
                foreach( $tags as $tk => $tv ) {
                    $final_value = str_replace('{'.$tv.'}', $v[$tv], $final_value);
                    $final_label = str_replace('{'.$tv.'}', $v[$tv], $final_label);
                }
                if($tag=='text') {
                    if($prefix=='keywords_'){
                        $item = '<li class="super-item" sfevents="' . esc_attr('{"click":"keywords.add"}') . '" data-value="' . esc_attr( $final_value ) . '" data-search-value="' . esc_attr( $final_label ) . '"' . SUPER_Common::get_tags_attributes($final_value) . '>';
                        $item .= '<span class="super-wp-tag"' . SUPER_Common::get_tags_attributes($final_label) . '>' . $final_label . '</span>'; 
                        $item .= '</li>';
                        $items[] = $item;
                    }else{
                        $items[] = '<li class="super-item" data-value="' . esc_attr( $final_value ) . '" data-search-value="' . esc_attr( $final_label ) . '"' . SUPER_Common::get_tags_attributes($final_value) . '><div' . SUPER_Common::get_tags_attributes($final_label) . '>' . $final_label . '</div></li>';
                    }
                }
                if($tag=='dropdown')    $items[] = '<li class="super-item" data-value="' . esc_attr( $final_value ) . '" data-search-value="' . esc_attr( $final_label ) . '"' . SUPER_Common::get_tags_attributes($final_value) . '><div' . SUPER_Common::get_tags_attributes($final_label) . '>' . $final_label . '</div></li>';
                if($tag=='checkbox')    $items[] = '<label class="super-item' . ( !in_array($final_value, $selected_items, true ) ? '' : ' super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><span class="super-before"><span class="super-after"></span></span><input' . ( !in_array($final_value, $selected_items, true ) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $final_value ) . '"' . SUPER_Common::get_tags_attributes($final_value) . ' /><div' . SUPER_Common::get_tags_attributes($final_label) . '>' . $final_label . '</div></label>';
                if($tag=='radio')       $items[] = '<label class="super-item' . ( ($atts['value']!=$final_value) ? '' : ' super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><span class="super-before"><span class="super-after"></span></span><input type="radio" value="' . esc_attr( $final_value ) . '"' . SUPER_Common::get_tags_attributes($final_value) . ' /><div' . SUPER_Common::get_tags_attributes($final_label) . '>' . $final_label . '</div></label>';
                $items_values[] = $final_value;
            }
        }
        
        // Set correct placeholder for dropdowns
        if($tag=='dropdown'){
            if(!empty($placeholder)){
                $atts['placeholder'] = implode(', ', $placeholder);
            }
        }
        if(empty($atts['value'])){
            $atts['value'] = implode( ',', $selected_items );
        }
        if(empty($items_values)) $items_values = array();
        return apply_filters( 'super_' . $tag . '_' . $atts['name'] . '_items_filter', array('items'=>$items, 'items_values'=>$items_values, 'atts'=>$atts), array( 'tag'=>$tag, 'atts'=>$atts, 'settings'=>$settings, 'entry_data'=>$entry_data ) );
    }

    
    /** 
     *  Output the element HTML on the builder page (create form) inside the preview area
     *
     * @param  string  $tag
     *
     *  @since      1.0.0
    */
    public static function output_builder_html($x) {
        extract(self::extract($x));

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
            foreach( $shortcodes[$group]['shortcodes'][$tag]['atts'] as $k => $v ) {
                if(!empty($v['fields'])){
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
                    if(!isset($v['fields'])) continue; 
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
            ); 
            $class .= ' super_'.$sizes[$data['size']][0] . ' super-' . str_replace( 'column_', 'super_', $tag );
            if(isset($data['invisible']) && $data['invisible']==='true'){
                $class .= ' super-is-invisible';
            }
        }
        if($tag=='multipart' || $tag=='tabs' ){
            $class .= ' super-' . $tag;
        }
  
        if( isset( $shortcodes[$group]['shortcodes'][$tag]['drop'] ) ) {
            $class .= ' drop-here';
            // But not if this is a TAB element
            if($tag!='tabs') {
                $inner_class .= ' super-dropable';
            }
        }
        
        if( (!empty($data['minimized'])) && ($data['minimized']=='yes') ) {
            $class .= ' super-minimized';
        }else{
            unset($data['minimized']);
        }
        
        if(!empty($data['align_elements'])){
            $class .= ' super-builder-align-inner-elements-' . $data['align_elements'];
        }

        if(!empty($data['duplicate']) && $data['duplicate']==='enabled'){
            $class .= ' super-duplicate-column-fields';
        }
        $result = '';
        
        // If we are updating a TAB element, we will send back a json string with all the content
        // Then on the JS side we will generate the HTML
        // Be aware that this is completely a different method/way then from the other elements
        // This is required because TAB element can have different layouts
        if($builder!==false){
            if( empty($data) ) $data = null;
            if( empty($inner) ) $inner = null;
            $i18n = (isset($_POST['i18n']) ? $_POST['i18n'] : '');
            //$result .= 'tag2: ' . $tag;
            $result .= self::output_element_html( array('grid'=>null, 'tag'=>$tag, 'group'=>$group, 'data'=>$data, 'inner'=>$inner, 'shortcodes'=>$shortcodes, 'settings'=>$settings, 'i18n'=>$i18n, 'builder'=>$builder) );
            return $result;
        }

        $result .= '<div class="super-element' . $class . '" data-shortcode-tag="' . $tag . '" data-group="'.$group.'" data-minimized="' . ( !empty($data['minimized']) ? 'yes' : 'no' ) . '" ' . ( $tag=='column' ? 'data-size="' . $data['size'] . '"' : '' ) . '>';
            $result .= '<div class="super-element-header">';
                if( ($tag=='column') || ($tag=='multipart') ){
                    $result .= '<div class="super-element-label">';
                    if(empty($data['label'])){
                        $label = $name;
                    }else{
                        $label = $data['label'];
                    } 
                    $result .= '<span>'.$label.'</span>';
                    $result .= '<input type="text" value="'.esc_attr($label).'" />';
                    $result .= '</div>';
                }
                if($tag=='column'){
                    $result .= '<div class="super-resize super-tooltip" data-content="Change Column Size">';
                        $result .= '<span class="smaller"><i class="fas fa-angle-left"></i></span>';
                        $result .= '<span class="current">' . $data['size'] . '</span><span class="bigger"><i class="fas fa-angle-right"></i></span>';
                    $result .= '</div>';
                }else{
                    $result .= '<div class="super-title">';
                    $result .= $name;
                    if( ($tag!='button') && ($tag!='button') && (isset($data['name'])) ) {
                        $result .= ' <input class="super-tooltip" title="Unique field name" type="text" value="' . esc_attr($data['name']) . '" autocomplete="false" />';
                    }
                    $result .= '</div>';
                }
                $result .= '<div class="super-element-actions">';
                    $result .= '<span class="super-edit super-tooltip" title="' . esc_html__( 'Edit element', 'super-forms' ) . '"><i class="fas fa-pencil-alt"></i></span>';
                    $result .= '<span class="super-duplicate super-tooltip" title="' . esc_html__( 'Duplicate element', 'super-forms' ) . '"><i class="fas fa-copy"></i></span>';
                    $result .= '<span class="super-move super-tooltip" title="' . esc_html__( 'Reposition element', 'super-forms' ) . '"><i class="fas fa-arrows-alt"></i></span>';
                    $result .= '<span class="super-transfer super-tooltip" title="' . esc_html__( 'Transfer this element (also works across forms)', 'super-forms' ) . '"><i class="fas fa-exchange-alt"></i></span>';
                    $result .= '<span class="super-transfer-drop super-tooltip" title="' . esc_html__( 'Transfer after this element', 'super-forms' ) . '"><i class="fas fa-arrow-circle-down"></i></span>';
                    $result .= '<span class="super-minimize super-tooltip" title="' . esc_html__( 'Minimize', 'super-forms' ) . '"><i class="fas fa-minus-square"></i></span>';
                    $result .= '<span class="super-delete super-tooltip" title="' . esc_html__( 'Delete', 'super-forms' ) . '"><i class="fas fa-times"></i></span>';
                $result .= '</div>';
            $result .= '</div>';
            
            // Check if this is a TAB element
            // TAB elements require a special loop because they have multiple "inner" objects
            if($tag=='tabs'){
                $result .= '<div class="super-element-inner">';
                    if( empty($data) ) $data = null;
                    if( empty($inner) ) $inner = null;
                    $i18n = (isset($_POST['i18n']) ? $_POST['i18n'] : '');
                    //$result .= 'tag3: ' . $tag;
                    $result .= self::output_element_html( array('grid'=>null, 'tag'=>$tag, 'group'=>$group, 'data'=>$data, 'inner'=>$inner, 'shortcodes'=>$shortcodes, 'settings'=>$settings, 'i18n'=>$i18n, 'builder'=>true) );
                $result .= '</div>';
            }else{
                $result .= '<div class="super-element-inner' . $inner_class . '">';
                    if( ( $tag!='column' ) && ( $tag!='multipart' ) ) {
                        if( empty($data) ) $data = null;
                        if( empty($inner) ) $inner = null;
                        $i18n = (isset($_POST['i18n']) ? $_POST['i18n'] : '');
                        //$result .= 'tag4: ' . $tag;
                        $result .= self::output_element_html( array('grid'=>null, 'tag'=>$tag, 'group'=>$group, 'data'=>$data, 'inner'=>$inner, 'shortcodes'=>$shortcodes, 'settings'=>$settings, 'i18n'=>$i18n, 'builder'=>false) );
                    }
                    if( !empty( $inner ) ) {
                        foreach( $inner as $k => $v ) {
                            if( empty($v['data'] ) ) $v['data'] = null;
                            if( empty($v['inner'] ) ) $v['inner'] = null;
                            $result .= self::output_builder_html( array('tag'=>$v['tag'], 'group'=>$v['group'], 'data'=>$v['data'], 'inner'=>$v['inner'], 'shortcodes'=>$shortcodes, 'settings'=>$settings) );
                        }
                    }
                $result .= '</div>';
            }
            $result .= '<textarea name="element-data">' . htmlentities( json_encode( $data ), ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED ) . '</textarea>';
        $result .= '</div>';
        
        return $result;
        
    }


    public static function opening_tag( $tag, $atts, $class='', $styles='') {
        $style = '';
        if($tag=='divider') $atts['width'] = 0;
        if($tag!='image'){
            if( !isset( $atts['width'] ) ) $atts['width'] = 0;
            if( $atts['width']!=0 ) $style .= 'width:' . $atts['width'] . 'px;';
        }
        if( !empty( $atts['tooltip'] ) ) {
            wp_enqueue_style( 'tooltips', SUPER_PLUGIN_FILE.'assets/css/backend/tooltips.css', array(), SUPER_VERSION );    
            wp_enqueue_script( 'tooltips', SUPER_PLUGIN_FILE.'assets/js/backend/tooltips.js', array( 'jquery' ), SUPER_VERSION, false );   
        }
        $result = '<div';
        if( ( $style!='' ) || ( $styles!='' ) ) $result .= ' style="' . $style . $styles . '"';
        $result .= ' class="super-shortcode super-field super-' . ($tag==='tinymce' ? 'html' : $tag);
        if( !empty($atts['label'])  && (!empty($atts['description'])) ) {
            $result .= ' super-has-label-desc';
        }else{
            if( !empty($atts['label'])  && (empty($atts['description'])) ) {
                $result .= ' super-has-only-label';
            }
            if( empty($atts['label'])  && (!empty($atts['description'])) ) {
                $result .= ' super-has-only-desc';
            }
        }
        $result .= ' ' . (!empty($atts['icon']) ? ' super-icon-' . $atts['icon_position'] . ' super-icon-' . $atts['icon_align'] : '');
        if(!empty($atts['value'])) $result .= ' super-filled';
        $align = '';
        if( isset( $atts['align'] ) ) $align = ' super-align-' . $atts['align'];
        $result .= $align;

        if( !empty( $atts['tooltip'] ) ) $result .= ' super-tooltip';
        if( !isset( $atts['error_position'] ) ) $atts['error_position'] = '';
        $result .= ' ' . $atts['error_position'];
        if( !isset( $atts['grouped'] ) ) $atts['grouped'] = 0;
        if($atts['grouped']==0) $result .= ' super-ungrouped ';
        if($atts['grouped']==1) $result .= ' super-grouped ';
        if($atts['grouped']==2) $result .= ' super-grouped super-grouped-end ';

        // If default value contains {tags} we will replace them on page load via javascript
        if(!empty($atts['value'])){
            $regex = '/\{(.*?)\}/';
            preg_match_all($regex, $atts['value'], $matches, PREG_SET_ORDER, 0);
            if(count($matches)>0){
                $class .= ' super-replace-tags';
            }
        }
        
        if( $tag!='hidden' ) {
            $conditionAttributes = self::conditional_attributes( $atts );
            $class .= $conditionAttributes['class'];
        }

        // @since 1.9 - custom wrapper class
        if($tag=='spacer'){
            if( !isset( $atts['class'] ) ) $atts['class'] = '';     
            $result .= ' ' . $class . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"';
        }else{
            if( !isset( $atts['wrapper_class'] ) ) $atts['wrapper_class'] = '';     
            $result .= ' ' . $class . ($atts['wrapper_class']!='' ? ' ' . $atts['wrapper_class'] : '') . '"';
        }

        if( !empty( $atts['tooltip'] ) ) $result .= ' title="' . esc_attr( stripslashes( $atts['tooltip'] ) ) . '"';
        if( $tag=='text' || $tag=='hidden' ) {
            $result .= self::conditional_variable_attributes( $atts );
        }
        if( $tag!='hidden' ) {
            $result .= $conditionAttributes['dataset']; 
        }

        // @since 3.2.0 - custom TAB index
        if( (isset($atts['custom_tab_index'])) && ($atts['custom_tab_index']>=0) ) {
            $result .= ' data-super-custom-tab-index="' . absint($atts['custom_tab_index']) . '"';   
        }
        $result .= self::pdf_attributes($atts);
        $result .= '>';
        if( !empty($atts['label']) ) {
            $bottom_margin = false;
            if( empty($atts['description']) ) {
                $bottom_margin = true;
            }
            $result .= self::field_label( $atts['label'], $bottom_margin );
        }
        if( !empty($atts['description']) ) {
            $result .= self::field_description( $atts['description'] );
        }

        // Display errors that need to be positioned below the field
        $result .= self::field_error_msg( $tag, $atts, 'top' );
        return $result;
    }

    public static function pdf_attributes( $atts ) {
        $result = '';
        // PDF option
        if( (isset($atts['pdfOption'])) && ($atts['pdfOption']>=0) ) {
            if($atts['pdfOption']!=='none') $result .= ' data-pdfOption="' . esc_attr($atts['pdfOption']) . '"';   
        }
        return $result;
    }
    public static function conditional_attributes( $atts ) {
        if( !isset( $atts['conditional_action'] ) ) $atts['conditional_action'] = 'disabled';
        if( !isset( $atts['conditional_trigger'] ) ) $atts['conditional_trigger'] = 'all';
        if( $atts['conditional_action']!='disabled' ) {
            $class = ' super-conditional-visible';
            if($atts['conditional_action']==='show'){
                $class = ' super-conditional-hidden';
            }
            return array( 'class' => $class, 'dataset' => ' data-conditional-action="' . $atts['conditional_action'] . '" data-conditional-trigger="' . $atts['conditional_trigger'] . '"');
        }
        return array('class'=>'', 'dataset'=>'');
    }
    public static function conditional_variable_attributes( $atts ) {
        if( !isset( $atts['conditional_variable_action'] ) ) $atts['conditional_variable_action'] = 'disabled';
        if( $atts['conditional_variable_action']!='disabled' ) {
            return ' data-conditional-variable-action="' . $atts['conditional_variable_action'] . '"';
        }
    }
    public static function field_label( $label, $bottom_margin ) {
        $class = '';
        if( $bottom_margin==true ) $class = ' super-bottom-margin';
        $atts = SUPER_Common::get_tags_attributes(stripslashes($label));
        return '<div class="super-label' . $class . '"'.$atts.'>' . stripslashes($label) . '</div>';
    }
    public static function field_description( $description ) {
        $atts = SUPER_Common::get_tags_attributes(stripslashes($description));
        return '<div class="super-description"'.$atts.'>' . stripslashes($description) . '</div>';
    }
    public static function field_error_msg( $tag, $atts, $position ) {
        // Do not render error message for non fields (those that do not have a name)
        if(!isset($atts['name'])) return '';
        if($tag=='column' || $tag=='multipart') return '';
        
        if(empty($atts['error'])) {
            $atts['error'] = esc_html__( 'Field is required!', 'super-forms' );
        }
        if(empty($atts['emptyError'])) { // if empty fall back to the validation error message
            $atts['emptyError'] = $atts['error'];
        }
        if( empty( $atts['error_position'] ) ) $atts['error_position'] = 'bottom-right';
        if($position=='top'){
            if($atts['error_position']=='top-left' || $atts['error_position']=='top-right'){
                return '<div class="super-error-msg">' . stripslashes($atts['error']) . '</div><div class="super-empty-error-msg">' . stripslashes($atts['emptyError']) . '</div>';
            }
        }
        if($position=='bottom'){
            if($atts['error_position']=='bottom-left' || $atts['error_position']=='bottom-right'){
                return '<div class="super-error-msg">' . stripslashes($atts['error']) . '</div><div class="super-empty-error-msg">' . stripslashes($atts['emptyError']) . '</div>';
            }
        }
    }   
    public static function opening_wrapper( $atts=array(), $inner=array(), $shortcodes=null, $settings=null, $wrapper_class=null ) {
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

        $result = '<div' . $style . ' class="super-field-wrapper ' . $wrapper_class . '">';
        if($atts['icon']!=''){
            $icon_tag = explode(' ', $atts['icon']);
            if(isset($icon_tag[1])){
                $icon_type = $icon_tag[0];
                $icon_tag = str_replace('fa-', '', $icon_tag[1]);
            }else{
                $default = explode(';', $atts['icon']);
                $icon_tag = $default[0];
                $icon_type = 'fas';
                if(isset($default[1])){
                    $icon_type = $default[1]; // use the existing type
                }
            }
            if( (empty($atts['display'])) || (!empty($atts['display']) && $atts['display']!='slider') ){
                $result .= '<i class="' . $icon_type . ' fa-'.SUPER_Common::fontawesome_bwc($icon_tag).' super-icon"></i>';
            }
        }
        return $result;
    }
    public static function common_attributes( $atts, $tag, $settings = array() ) {
        
        if( !isset( $atts['error'] ) ) $atts['error'] = '';
        if( !isset( $atts['validation'] ) ) $atts['validation'] = '';
        if( !isset( $atts['conditional_validation'] ) ) $atts['conditional_validation'] = '';
        if( !isset( $atts['conditional_validation_value'] ) ) $atts['conditional_validation_value'] = '';
        if( !isset( $atts['conditional_validation_value2'] ) ) $atts['conditional_validation_value2'] = ''; // @since 3.6.0
        if( !isset( $atts['may_be_empty'] ) ) $atts['may_be_empty'] = 'false';
        if( !isset( $atts['email'] ) ) $atts['email'] = $atts['name'];
        if( !isset( $atts['exclude'] ) ) $atts['exclude'] = 0;
        if( !isset( $atts['replace_commas'] ) ) $atts['replace_commas'] = '';
        if( !isset( $atts['exclude_entry'] ) ) $atts['exclude_entry'] = '';
        if( !isset( $atts['maxlength'] ) ) $atts['maxlength'] = 0;
        if( !isset( $atts['minlength'] ) ) $atts['minlength'] = 0;

        // @since 2.6.0 - IBAN validation
        if( $atts['validation']=='iban' ) {
            wp_enqueue_script( 'iban-check', SUPER_PLUGIN_FILE . 'assets/js/frontend/iban-check.js', array( 'jquery' ), SUPER_VERSION, false );
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

        if( !empty($atts['originalFieldName']) ) {
            // Original Field Name (required/used by dynamic columns, to allow nested dynamic columns, javascript uses this data attribute)
            $result .= ' data-oname="' . explode('[', $atts['originalFieldName'])[0] . '"';
        }

        if(!empty($atts['type']) && $atts['type']==='int-phone'){
            $data_attributes['int-phone'] = json_encode(array(
                'preferredCountries' => $atts['preferredCountries'],
                'onlyCountries' => $atts['onlyCountries'],
                'placeholderNumberType' => $atts['placeholderNumberType'],
                'localizedCountries' => $atts['localizedCountries']
            ));
        }

        foreach($data_attributes as $k => $v){
            if($v!=''){
                $result .= ' data-' . $k . '="' . esc_attr($v) . '"';
            }
        }
        
        // @since 4.7.7 - absolute default value based on settings
        if( isset($atts['absolute_default']) ) {
            $result .= ' data-absolute-default="' . esc_attr(SUPER_Common::email_tags( $atts['absolute_default'], null, $settings )) . '"';
        }

        
        // @since 2.0.0 - default value data attribute needed for Clear button
        if( isset($atts['value']) ) $result .= ' data-default-value="' . esc_attr($atts['value']) . '"';

        // @since 1.2.2
        if( !empty( $atts['disabled'] ) ) $result .= ' disabled="' . esc_attr($atts['disabled']) . '"';

        // @since 3.6.0
        if( !empty( $atts['readonly'] ) ) $result .= ' readonly="true"';

        // @since 3.6.0 - disable field autocompletion
        if( !empty($atts['autocomplete']) ) $result .= ' autocomplete="false"';

        // @since 4.9.3 - Adaptive Placeholders
        // If adaptive placeholders is enabled, we will not want to use the default placeholders
        if(!isset($settings['enable_adaptive_placeholders'])) $settings['enable_adaptive_placeholders'] = '';
        // Do not use Adaptive placeholder for minimal theme
        if(!empty($settings['theme_style']) && $settings['theme_style']=='super-style-one') $settings['enable_adaptive_placeholders'] = '';
        if( (empty($settings['enable_adaptive_placeholders'])) ) {
            if( !empty( $atts['default_placeholder'] ) ) {
                $result .= ' placeholder="' . esc_attr($atts['default_placeholder']) . '"';
            }else{
                if( !empty( $atts['placeholder'] ) ) {
                    $result .= ' placeholder="' . esc_attr($atts['placeholder']) . '"';
                }
            }
        }
        if($tag=='file'){
            if( $atts['minlength']>0 ) {
                $result .= ' data-minfiles="' . esc_attr($atts['minlength']) . '"';
            }
            if( $atts['maxlength']>0 ) {
                $result .= ' data-maxfiles="' . esc_attr($atts['maxlength']) . '"';
            }
        }elseif( $tag=='product' ) {
            if( $atts['maxlength']>0 ) {
                $result .= ' max="' . esc_attr($atts['maxlength']) . '" data-maxlength="' . esc_attr($atts['maxlength']) . '"';
            }
            $result .= ' min="' . esc_attr($atts['minlength']) . '" data-minlength="' . esc_attr($atts['minlength']) . '"';
        }elseif( ($tag=='dropdown') || ($tag=='checkbox') || ($tag=='radio') ) {    
            // @since 1.2.7
            if( !empty($atts['admin_email_value']) && $atts['admin_email_value']!='value' ) {
                $result .= ' data-admin-email-value="' . esc_attr($atts['admin_email_value']) . '"';
            }
            if( !empty($atts['confirm_email_value']) && $atts['confirm_email_value']!='value' ) {
                $result .= ' data-confirm-email-value="' . esc_attr($atts['confirm_email_value']) . '"';
            }

            // @since 1.2.9
            if( !empty($atts['contact_entry_value']) && $atts['contact_entry_value']!='value' ) {
                $result .= ' data-contact-entry-value="' . esc_attr($atts['contact_entry_value']) . '"';
            }

            if( ($tag=='dropdown') || ($tag=='checkbox') ) {
                // @since 2.0.0
                if( $atts['maxlength']>0 ) {
                    $result .= ' data-maxlength="' . esc_attr($atts['maxlength']) . '"';
                }
                if( $atts['minlength']>0 ) {
                    $result .= ' data-minlength="' . esc_attr($atts['minlength']) . '"';
                }
            }

        }else{
            if($tag=='date' || $tag=='time'){
                if( $atts['maxlength']!='' ) {
                    $result .= ' data-maxlength="' . esc_attr($atts['maxlength']) . '"';
                }
                if( $atts['minlength']!='' ) {
                    $result .= ' data-minlength="' . esc_attr($atts['minlength']) . '"';
                }
            }else{
                if( $tag=='text' ) {
                    // @since   1.3   - predefined input mask e.g: (___) ___-____
                    if( !empty($atts['mask']) ) {
                        wp_enqueue_script( 'super-masked-input', SUPER_PLUGIN_FILE . 'assets/js/frontend/masked-input.js', array( 'jquery' ), SUPER_VERSION, false );
                        $result .= ' data-mask="' . esc_attr($atts['mask']) . '"';
                    }
                    if( $atts['maxlength']>0 ) {
                        // For keyword field we do not want to set maxlength
                        if(!empty($atts['enable_keywords']) && $atts['enable_keywords']!='true'){
                            $result .= ' maxlength="' . esc_attr($atts['maxlength']) . '"';
                        }
                    }
                }
                if( $atts['maxlength']>0 ) {
                    $result .= ' data-maxlength="' . esc_attr($atts['maxlength']) . '"';
                }
                if( $atts['minlength']>0 ) {
                    $result .= ' data-minlength="' . esc_attr($atts['minlength']) . '"';
                }
            }

            if( !isset($atts['maxnumber'])) $atts['maxnumber'] = 0;
            if( !isset($atts['minnumber'])) $atts['minnumber'] = 0;
            if($atts['maxnumber']!=0 || $atts['minnumber']!=0){
                if( isset($atts['minnumber']) ) {
                    $result .= ' data-minnumber="' . esc_attr($atts['minnumber']) . '"';
                    if(isset($atts['type']) && $atts['type'] == 'number') {
                        $result .= ' min="' . esc_attr($atts['minnumber']) . '"';
                    }
                }
                if( (isset($atts['maxnumber'])) && ($atts['maxnumber'] > $atts['minnumber']) ) {
                    $result .= ' data-maxnumber="' . esc_attr($atts['maxnumber']) . '"';
                    if(isset($atts['type']) && $atts['type'] == 'number') {
                        $result .= ' max="' . esc_attr($atts['maxnumber']) . '"';
                    }
                }
            }
        }

        // @since 1.2.7     - super_common_attributes_filter
        return apply_filters( 'super_common_attributes_filter', $result, array( 'atts'=>$atts, 'tag'=>$tag ) );

    }

    // @since 4.9.3 - Adaptive Placeholders
    public static function adaptivePlaceholders( $settings, $atts, $tag ) {
        if($settings['theme_style']=='super-style-one') $settings['enable_adaptive_placeholders'] = '';
        if( (!empty($settings['enable_adaptive_placeholders'])) && (!empty($atts['placeholder'])) ) {
            if(empty($atts['placeholderFilled'])) $atts['placeholderFilled'] = $atts['placeholder'];
            $html = '<span class="super-adaptive-placeholder' . (!empty($settings['placeholder_adaptive_positioning']) ? ' super-adaptive-positioning' : '') . '" data-placeholder="' . esc_attr($atts['placeholder']) . '" data-placeholderFilled="' . esc_attr($atts['placeholderFilled']) . '">';
            $html .= '<span>' . $atts['placeholder'] . '</span>';
            $html .= '</span>';
            return $html;
        } 
    }

    // @since 1.2.5     - custom regex validation
    public static function custom_regex( $regex ) {
        if( !empty($regex) ) return '<textarea disabled class="super-custom-regex">' . $regex . '</textarea>';
    }

    public static function loop_conditions( $atts, $tag ) {

        $result = '';

        // @since 4.9.0 - Validate field only if condition is met
        if( (!empty($atts['may_be_empty'])) && ($atts['may_be_empty']=='conditions') ) {
            $names = array();
            if(is_array($atts['may_be_empty_conditions'])){
                foreach( $atts['may_be_empty_conditions'] as $k => $v ) {
                    if( !empty($v['field']) ) {
                        $names = SUPER_Common::get_data_fields_attribute( array( 'names'=>$names, 'value'=>$v['field'], 'bwc'=>true));
                        $names = SUPER_Common::get_data_fields_attribute( array( 'names'=>$names, 'value'=>$v['value']));
                    }
                    if( !empty($v['and_method']) && !empty($v['field_and']) ) {
                        $names = SUPER_Common::get_data_fields_attribute( array( 'names'=>$names, 'value'=>$v['field_and'], 'bwc'=>true));
                        $names = SUPER_Common::get_data_fields_attribute( array( 'names'=>$names, 'value'=>$v['value_and']));
                    }
                }
                $result .= '<textarea class="super-validate-conditions"' . (!empty($names) ? ' data-fields="{' . implode('}{', $names) . '}"' : '') . '>' . json_encode($atts['may_be_empty_conditions']) . '</textarea>';
            }
        }

        if( !isset( $atts['conditional_action'] ) ) $atts['conditional_action'] = 'disabled';
        if( !isset( $atts['conditional_items'] ) ) $atts['conditional_items'] = '';
        if( ( $atts['conditional_items']!=null ) && ( $atts['conditional_action']!='disabled' ) ) {
            // @since 4.2.0 - filter hook to change conditional items on the fly for specific element
            if( !empty($atts['name']) ) {
                $atts['conditional_items'] = apply_filters( 'super_conditional_items_' . $atts['name'] . '_filter', $atts['conditional_items'], array( 'atts'=>$atts ) );
            }
            // @since 2.3.0 - speed improvement for conditional logics
            // append the field names ad attribute that the conditions being applied to, so we can filter on it on field change with javascript
            $names = array();
            if(is_array($atts['conditional_items'])){
                foreach( $atts['conditional_items'] as $k => $v ) {
                    if( !empty($v['field']) ) {
                        $names = SUPER_Common::get_data_fields_attribute( array( 'names'=>$names, 'value'=>$v['field'], 'bwc'=>true));
                        $names = SUPER_Common::get_data_fields_attribute( array( 'names'=>$names, 'value'=>$v['value']));
                    }
                    if( !empty($v['and_method']) && !empty($v['field_and']) ) {
                        $names = SUPER_Common::get_data_fields_attribute( array( 'names'=>$names, 'value'=>$v['field_and'], 'bwc'=>true));
                        $names = SUPER_Common::get_data_fields_attribute( array( 'names'=>$names, 'value'=>$v['value_and']));
                    }
                }
            }
            // @since 1.7 - use json instead of HTML for speed improvements
            $result .= '<textarea class="super-conditional-logic"' . (!empty($names) ? ' data-fields="{' . implode('}{', $names) . '}"' : '') . '>' . json_encode($atts['conditional_items']) . '</textarea>';
        }

        // Display errors that need to be positioned below the field
        $result .= self::field_error_msg( $tag, $atts, 'bottom' );

        return $result;
    }
    
    // @since 1.2.7    - variable conditions
    public static function loop_variable_conditions( $atts ) {
        if( !isset( $atts['conditional_variable_action'] ) ) $atts['conditional_variable_action'] = 'disabled';
        if( $atts['conditional_variable_action']!='disabled' ) {
            if( !isset( $atts['conditional_items'] ) ) $atts['conditional_items'] = '';
            // Backwards compatibility to make sure old variable fields will keep working correctly.
            if( !empty( $atts['conditional_variable_items'] ) ) $atts['conditional_items'] = $atts['conditional_variable_items'];

            // @since 4.2.0 - variable conditions based on CSV file
            if( (!empty($atts['conditional_variable_method'])) && ($atts['conditional_variable_method']=='csv') ) {
                $delimiter = ',';
                $enclosure = '"';
                if( !empty( $atts['conditional_variable_delimiter'] ) ) $delimiter = $atts['conditional_variable_delimiter'];
                if( !empty( $atts['conditional_variable_enclosure'] ) ) $enclosure = stripslashes($atts['conditional_variable_enclosure']);
                if(strlen($delimiter)!==1) $delimiter = ',';
                if(strlen($enclosure)!==1) $enclosure = '"';
                $file = get_attached_file($atts['conditional_variable_csv']);
                $rows = array();
                $conditions = array();
                if( $file ) {
                    $row = 0;
                    if( (!empty($file)) && (($handle = fopen($file, "r")) !== FALSE) ) {
                        // Progress file pointer and get first 3 characters to compare to the BOM string.
                        $bom = "\xef\xbb\xbf"; // BOM as a string for comparison.
                        if (fgets($handle, 4) !== $bom) rewind($handle); // BOM not found - rewind pointer to start of file.
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
                                    'value_and' => (isset($columns[$kk]) ? $columns[$kk] : ''),
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
                $names = array();
                if(is_array($atts['conditional_items'])){
                    foreach( $atts['conditional_items'] as $k => $v ) {
                        if( !empty($v['field']) ) {
                            $names = SUPER_Common::get_data_fields_attribute( array( 'names'=>$names, 'value'=>$v['field'], 'bwc'=>true));
                            $names = SUPER_Common::get_data_fields_attribute( array( 'names'=>$names, 'value'=>$v['value']));
                        }
                        if( !empty($v['and_method']) && !empty($v['field_and']) ) {
                            $names = SUPER_Common::get_data_fields_attribute( array( 'names'=>$names, 'value'=>$v['field_and'], 'bwc'=>true));
                            $names = SUPER_Common::get_data_fields_attribute( array( 'names'=>$names, 'value'=>$v['value_and']));
                        }
                        if( !empty($v['new_value']) ) {
                            $names = SUPER_Common::get_data_fields_attribute( array( 'names'=>$names, 'value'=>$v['new_value']));
                        }
                    }
                }
                // @since 1.7 - use json instead of HTML for speed improvements
                return '<textarea class="super-variable-conditions"' . (!empty($names) ? ' data-fields="{' . implode('}{', $names) . '}"' : '') . '>' . json_encode($atts['conditional_items']) . '</textarea>';
            }
        }
    }


    public static function generate_element_stylesheet($group, $tag, $identifier, $atts, $shortcodes){
        $styles = '';
        $id = '#super-id-'.$identifier;
        if($shortcodes==false) $shortcodes = SUPER_Shortcodes::shortcodes();
        // Loop over all settings, and check if it needs to be added as a style
        // Loop over all possible tab settings, e.g: General, Styles etc.
        foreach($shortcodes[$group]['shortcodes'][$tag]['atts'] as $k => $v){
            // First check if 'fields' key exists
            if(isset($v['fields'])){
                // Loop over all fields, and look for 'selector' key
                foreach($v['fields'] as $fk => $fv){
                    if(empty($atts[$fk])) continue; // If value is 0 or empty we do not add the style
                    $styles .= self::loop_over_fields_to_generate_styles($id, $fk, $fv, $atts);
                }
            }else{
                // Dealing with subtabs
                // Loop over all subtabs, then look for 'selector' key
                unset($v['name']); // remove 'name' key
                foreach($v as $stk => $stv){
                    foreach($stv['fields'] as $fk => $fv){
                        if(empty($atts[$fk])) continue; // If value is 0 or empty we do not add the style
                        $styles .= self::loop_over_fields_to_generate_styles($id, $fk, $fv, $atts);
                    }
                }
            }
        }
        if(empty($styles)) return '';
        return '<style id="style-super-id-'.$identifier.'">'.$styles.'</style>';
    }
    public static function loop_over_fields_to_generate_styles($id, $fk, $fv, $atts){
        $styles = '';
        if(isset($fv['_styles'])){
            $value = $atts[$fk];
            foreach($fv['_styles'] as $sk => $sv){
                // Check if property contains a comma, if so we must add this style for all the properties specified
                $properties = explode(',', $sv);
                foreach($properties as $pv){
                    // Convert to proper justify-content
                    if($pv=='justify-content'){
                        if($value=='left') $value = 'flex-start';
                        if($value=='center') $value = 'center';
                        if($value=='right') $value = 'flex-end';
                    }
                    // Append "px" if needed
                    $suffix = '';
                    // In some cases we need to add "px", for instance with font size and line-height
                    if( ($pv=='font-size') || ($pv=='line-height') || (strpos($pv, 'margin-')) || (strpos($pv, '-radius')) || (strpos($pv, '-width')) ) {
                        $suffix = 'px';
                        $value = str_replace('px', '', $value); // Remove px from value if it contains any
                    }
                    $value = $value.$suffix;
                    $styles .= $id.$sk.' {';
                        $styles .= $pv . ': ' . str_replace(';', '', $value) . '!important;';
                    $styles .= '}';
                } 
            }
        }
        return $styles;
    }
    public static function tabs($x) {
        extract(self::extract($x));
        $group = 'layout_elements';
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, $group, $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation
        $result  = '';
        $layout = $atts['layout']; // possible values: tabs, accordion, list
        $location = (!empty($atts['tab_location']) ? ' super-' . $atts['tab_location'] : ' super-horizontal');
        $prev_next = (!empty($atts['tab_show_prev_next']) ? ' super-prev-next' : '');

        // Add stylesheets specific to this element
        $identifier = str_replace('.', '', microtime(true)).rand(1000000,9999999);
        $result .= self::generate_element_stylesheet($group, $tag, $identifier, $atts, $shortcodes);

        $result .= '<div id="super-id-'.$identifier.'" class="super-shortcode super-' . $tag . ' super-layout-' . $atts['layout'] . $location . $prev_next . (!empty($atts['class']) ? ' ' . $atts['class'] : '') . '"';
        $result .= self::pdf_attributes($atts);
        $result .= '>';
            // For each layout we need to generate a custom set of html
            if($layout=='tabs'){
                // Generate Tab layout
                // First generate the TAB menu (here will users click on to switch to a different TAB)
                $result .= '<div class="super-tabs-menu' . ($atts['tab_class']!='' ? ' ' . $atts['tab_class'] : '') . '">';
                $tab_html = '';
                foreach( $atts['items'] as $k => $v ) {
                    // First check if this TAB item has an image or not
                    $class = '';
                    $image = null;
                    if( !isset( $v['image'] ) ) $v['image'] = '';
                    if( $v['image']!='' ) {
                        $image = wp_get_attachment_image_src( $v['image'], 'original' );
                        $image = !empty( $image[0] ) ? $image[0] : '';
                        $class = ' super-has-image';
                    }
                    // Generate single TAB
                    // Make sure that the first TAB is active by default on page load
                    $tab_html .= '<div class="super-tabs-tab' . ($k==0 ? ' super-active' : '') . ( !empty($class) ? ' ' . $class : '') . '">';
                        if( !empty( $image ) ) {
                            if( empty( $v['max_width'] ) ) $v['max_width'] = 50;
                            if( empty( $v['max_height'] ) ) $v['max_height'] = 50;
                            $img_styles = '';
                            if( $v['max_width']!='' ) $img_styles .= 'max-width:' . $v['max_width'] . 'px;';
                            if( $v['max_height']!='' ) $img_styles .= 'max-height:' . $v['max_height'] . 'px;';
                            $tab_html .= '<div class="super-tab-image"><img src="' . esc_url($image) . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                        }
                        // Tab title
                        $tab_html .= '<div class="super-tab-title"';
                        $tab_html .= SUPER_Common::get_tags_attributes($v['title']);
                        $tab_html .= '>' . $v['title'] . '</div>';
                        // Tab description
                        $tab_html .= '<div class="super-tab-desc"';
                        $tab_html .= SUPER_Common::get_tags_attributes($v['desc']);
                        $tab_html .= '>' . $v['desc'] . '</div>';
                        $tab_html .= '<span class="super-tab-prev"></span>';
                        $tab_html .= '<span class="super-tab-next"></span>';
                    $tab_html .= '</div>';
                }
                
                // If the only thing that we need to do is update the TABS in the back-end (builder page)
                // Then send a json string back to the JS create-form.js
                // This will then update the TABS html, and also remove or add missing TAB content
                if($builder!==false && is_array($builder)){
                    $from = $builder[0];
                    $to = $builder[1];
                    // From TABS to TABS layout (no change)
                    // We will just want to update the TAB menu here
                    if($from=='tabs' && $to=='tabs'){
                        // Generate the TAB html
                        $json = array(
                            'tag' => $tag,
                            'builder' => $builder,
                            'html' => $tab_html
                        );
                        return json_encode($json);
                    }
                }
                
                $result .= $tab_html;
                $result .= '</div>';
                // End of TAB menu

                // Now generate the actual TAB content (with their inner elements)
                $result .= '<div class="super-tabs-contents' . ($atts['content_class']!='' ? ' ' . $atts['content_class'] : '') . '">';
                foreach( $atts['items'] as $k => $v ) {
                    if(empty($inner)) $inner = null;
                    if(empty($inner[$k])) $inner[$k] = null;
                    $tab_inner = $inner[$k];
                    // Generate single TAB content
                    // Make sure that the first TAB is active by default on page load
                    $result .= '<div class="super-tabs-content' . ($k==0 ? ' super-active' : '') . '">';
                        $result .= '<div class="super-padding">';
                            if($builder) $result .= '<div class="super-element-inner super-dropable">';
                            if( !empty($tab_inner) ) {
                                // Loop through inner elements
                                foreach($tab_inner as $ik => $iv){
                                    if( !empty($iv['data']['name']) ) {
                                        $inner_field_names[$iv['data']['name']] = array(
                                            'name' => (isset($iv['data']['name']) ? $iv['data']['name'] : 'undefined'), // Field name
                                            'email' => (isset($iv['data']['email']) ? $iv['data']['email'] : '') // Email label
                                        );
                                    }
                                }
                                // First check how many columns there are
                                // This way we can correctly close the column system
                                $initialColumnsFound = (empty($GLOBALS['super_column_found']) ? 0 : $GLOBALS['super_column_found']);
                                $GLOBALS['super_column_found'] = 0;
                                foreach( $tab_inner as $iv ) {
                                    if( $iv['tag']=='column' ) $GLOBALS['super_column_found']++;
                                }
                                $re = '/\{(.*?)\}/';
                                $i = $dynamic;
                                foreach( $tab_inner as $iv ) {
                                    if( empty($iv['data']) ) $iv['data'] = null;
                                    if( empty($iv['inner']) ) $iv['inner'] = null;
                                    if($builder){
                                        $result .= self::output_builder_html( array('tag'=>$iv['tag'], 'group'=>$iv['group'], 'data'=>$iv['data'], 'inner'=>$iv['inner'], 'shortcodes'=>$shortcodes, 'settings'=>$settings) );
                                    }else{
                                        $iv = SUPER_Common::replace_tags_dynamic_columns( array('v'=>$iv, 're'=>$re, 'i'=>$i, 'dynamic_field_names'=>$dynamic_field_names, 'inner_field_names'=>$inner_field_names) );
                                        //$result .= 'tag5: ' . $iv['tag'];
                                        $result .= self::output_element_html( array('grid'=>null, 'tag'=>$iv['tag'], 'group'=>$iv['group'], 'data'=>$iv['data'], 'inner'=>$iv['inner'], 'shortcodes'=>$shortcodes, 'settings'=>$settings, 'i18n'=>$i18n, 'builder'=>false, 'entry_data'=>$entry_data, 'dynamic'=>$dynamic, 'dynamic_field_names'=>$dynamic_field_names, 'inner_field_names'=>$inner_field_names, 'formProgress'=>$formProgress) );
                                    }
                                }
                                // Restore amount of columns found
                                $GLOBALS['super_column_found'] = $initialColumnsFound;
                            }
                            unset($GLOBALS['super_grid_system']);
                            if($builder) $result .= '</div>';
                        $result .= '</div>';
                    $result .= '</div>';
                }

                    // Prev & Next buttons
                    $result .= '<div class="super-content-prev">';
                        $result .= '<i class="top-line"></i>';
                        $result .= '<i class="bottom-line"></i>';
                    $result .= '</div>';
                    $result .= '<div class="super-content-next">';
                        $result .= '<i class="top-line"></i>';
                        $result .= '<i class="bottom-line"></i>';
                    $result .= '</div>';

                $result .= '</div>';
                // End of TAB contents

            }
            if($layout=='accordion'){
                // If the only thing that we need to do is update the Accordion header in the back-end (builder page)
                // Then send a json string back to the JS create-form.js
                // This will then update the Accordion header items, 
                if($builder!==false && is_array($builder)){
                    $from = $builder[0];
                    $to = $builder[1];
                    $header_items = $atts['items'];
                    // From Accordion to Accordion layout (no change)
                    // We will just want to update the Accordion header items here
                    if($from=='accordion' && $to=='accordion'){
                        // Return the Accordion header items
                        $json = array(
                            'builder' => $builder,
                            'header_items' => $header_items
                        );
                        return json_encode($json);
                    }
                }
                
                // Generate Accordion layout
                foreach( $atts['items'] as $k => $v ) {
                    // First check if this Accordion item has an image or not
                    $class = '';
                    $image = null;
                    if( !isset( $v['image'] ) ) $v['image'] = '';
                    if( $v['image']!='' ) {
                        $image = wp_get_attachment_image_src( $v['image'], 'original' );
                        $image = !empty( $image[0] ) ? $image[0] : '';
                        $class = ' super-has-image';
                    }
                    // Check if item has inner elements
                    if(empty($inner)) $inner = null;
                    if(empty($inner[$k])) $inner[$k] = null;
                    $tab_inner = $inner[$k];
                    // Generate the Accordion item
                    $result .= '<div class="super-accordion-item' . ( !empty($class) ? ' ' . $class : '') . '">';
                        $result .= '<div class="super-accordion-header' . ($atts['tab_class']!='' ? ' ' . $atts['tab_class'] : '') . '">';
                            if( !empty( $image ) ) {
                                if( empty( $v['max_width'] ) ) $v['max_width'] = 50;
                                if( empty( $v['max_height'] ) ) $v['max_height'] = 50;
                                $img_styles = '';
                                if( $v['max_width']!='' ) $img_styles .= 'max-width:' . $v['max_width'] . 'px;';
                                if( $v['max_height']!='' ) $img_styles .= 'max-height:' . $v['max_height'] . 'px;';
                                $result .= '<div class="super-accordion-image"><img src="' . esc_url($image) . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                            }
                            // Title
                            $result .= '<div class="super-accordion-title"' . SUPER_Common::get_tags_attributes($v['title']) . '>' . ($v['title']) . '</div>';
                            // Description
                            $result .= '<div class="super-accordion-desc"' . SUPER_Common::get_tags_attributes($v['desc']) . '>' . ($v['desc']) . '</div>';
                        $result .= '</div>';
                        $result .= '<div class="super-accordion-content' . ($atts['content_class']!='' ? ' ' . $atts['content_class'] : '') . '">';
                            $result .= '<div class="super-padding">';
                                if($builder) $result .= '<div class="super-element-inner super-dropable">';
                                if( !empty($tab_inner) ) {
                                    // Loop through inner elements
                                    foreach($tab_inner as $ik => $iv){
                                        if( !empty($iv['data']['name']) ) {
                                            $inner_field_names[$iv['data']['name']] = array(
                                                'name' => (isset($iv['data']['name']) ? $iv['data']['name'] : 'undefined'), // Field name
                                                'email' => (isset($iv['data']['email']) ? $iv['data']['email'] : '') // Email label
                                            );
                                        }
                                    }
                                    // First check how many columns there are
                                    // This way we can correctly close the column system
                                    $initialColumnsFound = (empty($GLOBALS['super_column_found']) ? 0 : $GLOBALS['super_column_found']);
                                    $GLOBALS['super_column_found'] = 0;
                                    foreach( $tab_inner as $iv ) {
                                        if( $iv['tag']=='column' ) $GLOBALS['super_column_found']++;
                                    }
                                    $re = '/\{(.*?)\}/';
                                    $i = $dynamic;
                                    foreach( $tab_inner as $iv ) {
                                        if( empty($iv['data']) ) $iv['data'] = null;
                                        if( empty($iv['inner']) ) $iv['inner'] = null;
                                        if($builder){
                                            $result .= self::output_builder_html( array('tag'=>$iv['tag'], 'group'=>$iv['group'], 'data'=>$iv['data'], 'inner'=>$iv['inner'], 'shortcodes'=>$shortcodes, 'settings'=>$settings) );
                                        }else{
                                            $iv = SUPER_Common::replace_tags_dynamic_columns( array('v'=>$iv, 're'=>$re, 'i'=>$i, 'dynamic_field_names'=>$dynamic_field_names, 'inner_field_names'=>$inner_field_names) );
                                            $result .= self::output_element_html( array('grid'=>null, 'tag'=>$iv['tag'], 'group'=>$iv['group'], 'data'=>$iv['data'], 'inner'=>$iv['inner'], 'shortcodes'=>$shortcodes, 'settings'=>$settings, 'i18n'=>$i18n, 'builder'=>false, 'entry_data'=>$entry_data, 'dynamic'=>$dynamic, 'dynamic_field_names'=>$dynamic_field_names, 'inner_field_names'=>$inner_field_names, 'formProgress'=>$formProgress) );
                                        }
                                    }
                                    // Restore amount of columns found
                                    $GLOBALS['super_column_found'] = $initialColumnsFound;
                                }
                                unset($GLOBALS['super_grid_system']);
                                if($builder) $result .= '</div>';
                            $result .= '</div>';
                        $result .= '</div>';
                    $result .= '</div>';
                }
            }
            if($layout=='list'){
                // Generate List layout
                $result .= 'List layout';
            }

        $result .= '</div>';
        return $result;
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
    public static function multipart($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'layout_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        // @since 2.6.0 - add active class to the first multipart element
        if( !isset($GLOBALS['super_first_multipart']) ) {
            $GLOBALS['super_first_multipart'] = true;
            $atts['class'] = 'super-active '.$atts['class']; 
        }

        $result  = '';
        $result .= '<div class="super-shortcode super-' . $tag . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" ' . ($atts['validate']=='true' ? ' data-validate="' . $atts['validate'] . '"' : '') . 'data-step-auto="' . $atts['auto'] .'"';
        
        // @since 4.2.0 - disable scrolling when multi-part contains errors
        if( !empty($atts['disable_scroll']) ) $result .= ' data-disable-scroll="true"';

        // @since 4.3.0 - disable scrolling for multi-part next prev
        if( !empty($atts['disable_scroll_pn']) ) $result .= ' data-disable-scroll-pn="true"';

        // @since 1.2.5
        if( isset( $atts['prev_text'] ) ) $result .= ' data-prev-text="' . $atts['prev_text'] . '"';
        if( isset( $atts['next_text'] ) ) $result .= ' data-next-text="' . $atts['next_text'] . '"';
        
        // @since 3.6.0 - disable autofocus first field
        if( !empty( $atts['autofocus'] ) ) $result .= ' data-disable-autofocus="true"';

        // @since 4.9.0 - display image if set, otherwise the icon if set
        if( !empty($atts['step_image']) ) {
            if( !isset( $atts['step_image'] ) ) $atts['step_image'] = 0;
            $attachment_id = absint($atts['step_image']);
            if( $attachment_id===0 ) {
                $url = SUPER_PLUGIN_FILE . 'assets/images/image-icon.png';
            }else{
                $url = wp_get_attachment_url( $attachment_id );
            } 
            $result .= ' data-image="' . esc_url($url) . '"';
        }else{
            if( !empty($atts['show_icon']) ) {
                $result .= ' data-icon="' . esc_attr($atts['icon']) . '"';
            }
        }
        if( !empty($atts['step_name']) ) {
            $result .= ' data-step-name="' . esc_attr($atts['step_name']) . '"';
        }
        if( !empty($atts['step_description']) ) {
            $result .= ' data-step-description="' . esc_attr($atts['step_description']) . '"';
        }

        $result .= self::pdf_attributes($atts);
        $result .= '>';

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
                $result .= self::output_element_html( array('grid'=>null, 'tag'=>$v['tag'], 'group'=>$v['group'], 'data'=>$v['data'], 'inner'=>$v['inner'], 'shortcodes'=>$shortcodes, 'settings'=>$settings, 'i18n'=>$i18n, 'builder'=>false, 'entry_data'=>$entry_data, 'dynamic'=>0, 'dynamic_field_names'=>array(), 'inner_field_names'=>array(), 'formProgress'=>$formProgress) );
            }
        }
        unset($GLOBALS['super_grid_system']);
        $result .= '</div>';
        return $result;
    }
    public static function column($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'layout_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
       
        if($atts['invisible']=='true') $atts['invisible'] = ' super-invisible';
        if(!empty($atts['align_elements'])){
            $atts['align_elements'] = ' super-align-inner-elements-' . $atts['align_elements'];
        }

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
                'dynamicLevel' => -1,
                'dynamicLevelId' => array(),
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

        if( empty($atts['margin']) ) $atts['margin'] = '';

        $conditionAttributes = self::conditional_attributes( $atts );
        $class .= $conditionAttributes['class'];
        $result .= '<div class="super-shortcode super_' . $sizes[$atts['size']][0] . ' super-column'.$atts['invisible'].$atts['align_elements'].' grid-level-'.$grid['level'].' column-number-'.$grid['columns'][$grid['level']]['current'].' ' . $class . ' ' . $atts['margin'] . ($atts['resize_disabled_mobile']==true ? ' super-not-responsive' : '') . ($atts['resize_disabled_mobile_window']==true ? ' super-not-responsive-window' : '') . ($atts['hide_on_mobile']==true ? ' super-hide-mobile' : '') . ($atts['hide_on_mobile_window']==true ? ' super-hide-mobile-window' : '') . ($atts['force_responsiveness_mobile_window']==true ? ' super-force-responsiveness-window' : '') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"' . $styles; 
        $result .= $conditionAttributes['dataset']; 
        if( $atts['duplicate']=='enabled' ) {
            // @since   1.2.8    - make sure this data is set
            if( !isset( $atts['duplicate_limit'] ) ) $atts['duplicate_limit'] = 0;
            $result .= ' data-duplicate-limit="' . $atts['duplicate_limit'] . '"';

            // @since 1.3
            if( !isset( $atts['duplicate_dynamically'] ) ) $atts['duplicate_dynamically'] = '';
            if($atts['duplicate_dynamically']=='true') {
                $result .= ' data-duplicate-dynamically="' . $atts['duplicate_dynamically'] . '"';
            }
        }
        $result .= self::pdf_attributes($atts);
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
            if($atts['duplicate']==='enabled' && $entry_data){
                $GLOBALS['super_grid_system'] = $grid;
                $GLOBALS['super_column_found'] = 0;

                // Before we proceed we must fetch all the inner field names
                // This way we can check if a specific field exists inside the dynamic column when replacing {tags}.
                // If no field name exists we will not replace the {tag} and thus not increment from {tag} to {tag_2} to {tag_3} etc.
                $re = '/{"name":"(.*?)"/';
                $str = json_encode($inner);
                preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                $dynamic_field_names = array();
                foreach($matches as $mk => $mv){
                    $dynamic_field_names[] = $mv[1];
                }

                // Loop through inner elements
                foreach($inner as $ik => $iv){
                    if( !empty($iv['data']['name']) ) {
                        $inner_field_names[$iv['data']['name']] = array(
                            'name' => (isset($iv['data']['name']) ? $iv['data']['name'] : 'undefined'), // Field name
                            'email' => (isset($iv['data']['email']) ? $iv['data']['email'] : '') // Email label
                        );
                    }
                }

                // Grab first field name inside the dynamic column
                // This is always the index on the saved data
                $regex = '/"name":"(.*?)",/';
                preg_match($regex, $str, $matches, PREG_OFFSET_CAPTURE, 0);
                if( isset($matches[1]) && isset($matches[1][0]) ){
                    $field_name = $matches[1][0];
                    $no_data = false;
                    if(isset($entry_data['_super_dynamic_data'])){
                        if(!is_array($entry_data['_super_dynamic_data'])){
                            $_super_dynamic_data = json_decode($entry_data['_super_dynamic_data'], true);
                        }else{
                            $_super_dynamic_data = $entry_data['_super_dynamic_data'];
                        }
                        if( (is_array($_super_dynamic_data)) && (!empty($_super_dynamic_data[$field_name])) ) {
                            $i=1;
                            $re = '/\{(.*?)\}/';
                            foreach($_super_dynamic_data[$field_name] as $dk => $dv){
                                $grid['level']++;
                                $GLOBALS['super_grid_system'] = $grid;
                                $GLOBALS['super_column_found'] = 0;
                                $result .= '<div class="super-shortcode super-duplicate-column-fields">';
                                    foreach( $inner as $k => $v ) {
                                        if( $v['tag']=='column' ) $GLOBALS['super_column_found']++;
                                    }
                                    foreach( $inner as $k => $v ) {
                                        if( empty($v['data']) ) $v['data'] = null;
                                        if( empty($v['inner']) ) $v['inner'] = null;
                                        $v = SUPER_Common::replace_tags_dynamic_columns( array('v'=>$v, 're'=>$re, 'i'=>$i, 'dynamic_field_names'=>$dynamic_field_names, 'inner_field_names'=>$inner_field_names, 'dv'=>$dv) );
                                        $result .= self::output_element_html( array('grid'=>$grid, 'tag'=>$v['tag'], 'group'=>$v['group'], 'data'=>$v['data'], 'inner'=>$v['inner'], 'shortcodes'=>$shortcodes, 'settings'=>$settings, 'i18n'=>$i18n, 'builder'=>false, 'entry_data'=>$entry_data, 'dynamic'=>$i, 'dynamic_field_names'=>$dynamic_field_names, 'inner_field_names'=>$inner_field_names, 'formProgress'=>$formProgress) );
                                    }
                                    $result .= '<div class="super-duplicate-actions">';
                                    $result .= '<span class="super-add-duplicate"></span>';
                                    $result .= '<span class="super-delete-duplicate"></span>';
                                    $result .= '</div>';
                                $result .= '</div>';
                                $grid['level']--;
                                $i++;
                            }
                        }else{
                            $no_data = true;
                        }
                    }else{
                        $no_data = true;
                    }
                    if($no_data){
                        $grid['level']++;
                        $GLOBALS['super_grid_system'] = $grid;
                        $GLOBALS['super_column_found'] = 0;
                        // No data found, let's generate at least 1 column
                        $result .= '<div class="super-shortcode super-duplicate-column-fields">';
                            foreach( $inner as $k => $v ) {
                                if( $v['tag']=='column' ) $GLOBALS['super_column_found']++;
                            }
                            foreach( $inner as $k => $v ) {
                                if( empty($v['data']) ) $v['data'] = null;
                                if( empty($v['inner']) ) $v['inner'] = null;
                                $result .= self::output_element_html( array('grid'=>$grid, 'tag'=>$v['tag'], 'group'=>$v['group'], 'data'=>$v['data'], 'inner'=>$v['inner'], 'shortcodes'=>$shortcodes, 'settings'=>$settings, 'i18n'=>$i18n, 'builder'=>false, 'entry_data'=>$entry_data, 'dynamic'=>0, 'dynamic_field_names'=>array(), 'inner_field_names'=>array(), 'formProgress'=>$formProgress) );
                            }
                            $result .= '<div class="super-duplicate-actions">';
                            $result .= '<span class="super-add-duplicate"></span>';
                            $result .= '<span class="super-delete-duplicate"></span>';
                            $result .= '</div>';
                        $result .= '</div>';
                        $grid['level']--;
                    }else{
                    }
                }
            }else{
                // Loop through inner elements
                foreach($inner as $ik => $iv){
                    if( !empty($iv['data']['name']) ) {
                        $inner_field_names[$iv['data']['name']] = array(
                            'name' => (isset($iv['data']['name']) ? $iv['data']['name'] : 'undefined'), // Field name
                            'email' => (isset($iv['data']['email']) ? $iv['data']['email'] : '') // Email label
                        );
                    }
                }
                
                $grid['level']++;
                if($atts['duplicate']==='enabled') {
                    $grid['dynamicLevel']++;
                }
                $GLOBALS['super_grid_system'] = $grid;
                $GLOBALS['super_column_found'] = 0;
                if( $atts['duplicate']==='enabled' ) {
                    $result .= '<div class="super-shortcode super-duplicate-column-fields">';
                }
                foreach( $inner as $k => $v ) {
                    if( $v['tag']=='column' ) $GLOBALS['super_column_found']++;
                }
                $re = '/\{(.*?)\}/';
                $i = $dynamic;
                foreach( $inner as $k => $v ) {
                    if( empty($v['data']) ) $v['data'] = null;
                    if( empty($v['inner']) ) $v['inner'] = null;
                    $v = SUPER_Common::replace_tags_dynamic_columns( array('v'=>$v, 're'=>$re, 'i'=>$i, 'dynamic_field_names'=>$dynamic_field_names, 'inner_field_names'=>$inner_field_names) );
                    $result .= self::output_element_html( array('grid'=>$grid, 'tag'=>$v['tag'], 'group'=>$v['group'], 'data'=>$v['data'], 'inner'=>$v['inner'], 'shortcodes'=>$shortcodes, 'settings'=>$settings, 'i18n'=>$i18n, 'builder'=>false, 'entry_data'=>$entry_data, 'dynamic'=>$dynamic, 'dynamic_field_names'=>$dynamic_field_names, 'inner_field_names'=>$inner_field_names, 'formProgress'=>$formProgress) );
                }
                if( $atts['duplicate']==='enabled' ) {
                    $result .= '<div class="super-duplicate-actions">';
                    $result .= '<span class="super-add-duplicate"></span>';
                    $result .= '<span class="super-delete-duplicate"></span>';
                    $result .= '</div>';
                    $result .= '</div>';
                }
                $grid['level']--;
            }
            $GLOBALS['super_grid_system'] = $grid;      
        }

        // @since   1.3   - column custom padding
        if( $close_custom_padding==true ) {
            $result .= '</div>';
        }

        $result .= self::loop_conditions( $atts, $tag );

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
    public static function quantity_field($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        if( empty($atts['wrapper_width']) ) $atts['wrapper_width'] = 50;
        if( empty($settings['theme_field_size']) ) $settings['theme_field_size'] = 'medium';
        if( $settings['theme_field_size']=='large' ) $atts['wrapper_width'] = $atts['wrapper_width']+20;
        if( $settings['theme_field_size']=='huge' ) $atts['wrapper_width'] = $atts['wrapper_width']+30;

        // Get default value
        $atts['value'] = self::get_default_value($tag, $atts, $settings, $entry_data, '0');
        
        $result = self::opening_tag( $tag, $atts );
        $result .= '<div class="super-quantity-field-wrap">';
            $result .= '<span class="super-minus-button super-noselect"><i>-</i></span>';
            $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

            // @since 1.9 - custom class
            if( !isset( $atts['class'] ) ) $atts['class'] = '';

            $result .= '<input tabindex="-1" class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="text"';

            if( empty( $atts['minnumber']) ) $atts['minnumber'] = 0;
            if( empty( $atts['maxnumber']) ) $atts['maxnumber'] = 100;
            if( $atts['value']<$atts['minnumber'] ) {
                $atts['value'] = $atts['minnumber'];
            }
            if( $atts['value']>$atts['maxnumber'] ) {
                $atts['value'] = $atts['maxnumber'];
            }

            $result .= ' name="' . $atts['name'] . '" value="' . esc_attr($atts['value']) . '" data-steps="' . $atts['steps'] . '"';
            $result .= self::common_attributes( $atts, $tag, $settings );
            $result .= ' />';

            // @since 1.2.5     - custom regex validation
            if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

            $result .= '</div>';
            $result .= '<span class="super-plus-button super-noselect"><i>+</i></span>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }


    /** 
     *  Toggle field
     *
     *  @since      2.9.0
    */    
    public static function toggle_field($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        // Based on current value update to final on/off value
        // This way we can correclty check if the toggle should be set to be active or not.
        if($atts['value']=='1'){
            $atts['value'] = $atts['on_value'];
        }else{
            $atts['value'] = $atts['off_value'];
        }

        $atts['validation'] = 'empty';

        // Get default value
        $atts['value'] = self::get_default_value($tag, $atts, $settings, $entry_data, '0');

        $result = self::opening_tag( $tag, $atts );
        
        // required to add a new line between label/description and the toggle itself
        $result .= '<div class="super-break"></div>';

        if(!isset($atts['prefix_label'])) $atts['prefix_label'] = '';
        if(!isset($atts['prefix_tooltip'])) $atts['prefix_tooltip'] = '';
        if( ($atts['prefix_label']!='') || ($atts['prefix_tooltip']!='') ) {
            $result .= '<div class="super-toggle-prefix-label">';
            if($atts['prefix_label']!='') $result .= '<span>'.$atts['prefix_label'].'</span>';
            if($atts['prefix_tooltip']!='') $result .= '<span class="super-toggle-prefix-question super-tooltip" title="' . esc_attr( stripslashes( $atts['prefix_tooltip'] ) ) . '"></span>';
            $result .= '</div>';
        }

        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        $toggle_active = false;
        if($atts['value']==$atts['on_value']){
            $toggle_active = true;
        }

        $result .= '<div class="super-toggle-switch ' . ( $toggle_active ? 'super-active' : '' ) . '">';
            $result .= '<div class="super-toggle-group">';
                $result .= '<label class="super-toggle-on" data-value="' . esc_attr($atts['on_value']) . '"><span>' . $atts['on_label'] . '</span></label>';
                $result .= '<span class="super-toggle-handle"></span>';
                $result .= '<label class="super-toggle-off" data-value="' . esc_attr($atts['off_value']) . '"><span>' . $atts['off_label'] . '</span></label>';
            $result .= '</div>';
        $result .= '</div>';

        if( !isset($atts['class']) ) $atts['class'] = '';
        $result .= '<input class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="hidden"';
        $result .= ' name="' . $atts['name'] . '" value="' . ( $toggle_active ? esc_attr($atts['on_value']) : esc_attr($atts['off_value']) ) . '"';
        $result .= self::common_attributes( $atts, $tag, $settings );
        $result .= ' />';

        $result .= '</div>';
        
        if( !isset($atts['suffix_label']) ) $atts['suffix_label'] = '';
        if( !isset($atts['suffix_tooltip']) ) $atts['suffix_tooltip'] = '';
        if( ($atts['suffix_label']!='') || ($atts['suffix_tooltip']!='') ) {
            $result .= '<div class="super-toggle-suffix-label">';
            if($atts['suffix_label']!='') $result .= '<span>'.$atts['suffix_label'].'</span>';
            if($atts['suffix_tooltip']!='') $result .= '<span class="super-toggle-suffix-question super-tooltip" title="' . esc_attr( stripslashes( $atts['suffix_tooltip'] ) ) . '"></span>';
            $result .= '</div>';
        }

        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }


    /** 
     *  Color picker
     *
     *  @since      3.1.0
    */    
    public static function color($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        wp_enqueue_style( 'spectrum', SUPER_PLUGIN_FILE.'assets/css/frontend/spectrum.css', array(), SUPER_VERSION );    
        wp_enqueue_script( 'spectrum', SUPER_PLUGIN_FILE . 'assets/js/frontend/spectrum.js', array( 'jquery' ), SUPER_VERSION, false );

        // Get default value
        $atts['value'] = self::get_default_value($tag, $atts, $settings, $entry_data);

        $result = self::opening_tag( $tag, $atts );

        // required to add a new line between label/description and the toggle itself
        $result .= '<div class="super-break"></div>';

        if( !isset($atts['prefix_label']) ) $atts['prefix_label'] = '';
        if( !isset($atts['prefix_tooltip']) ) $atts['prefix_tooltip'] = '';
        if( ($atts['prefix_label']!='') || ($atts['prefix_tooltip']!='') ) {
            $result .= '<div class="super-toggle-prefix-label">';
            if($atts['prefix_label']!='') $result .= '<span>'.$atts['prefix_label'].'</span>';
            if($atts['prefix_tooltip']!='') $result .= '<span class="super-toggle-prefix-question super-tooltip" title="' . esc_attr( stripslashes( $atts['prefix_tooltip'] ) ) . '"></span>';
            $result .= '</div>';
        }

        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<input tabindex="-1" class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="text"';
        $result .= ' name="' . $atts['name'] . '" value="' . esc_attr($atts['value']) . '"';
        $result .= self::common_attributes( $atts, $tag, $settings );
        $result .= ' />';
        $result .= '</div>';

        if( !isset( $atts['suffix_label'] ) ) $atts['suffix_label'] = '';
        if( !isset( $atts['suffix_tooltip'] ) ) $atts['suffix_tooltip'] = '';
        if( ($atts['suffix_label']!='') || ($atts['suffix_tooltip']!='') ) {
            $result .= '<div class="super-toggle-suffix-label">';
            if($atts['suffix_label']!='') $result .= '<span>'.$atts['suffix_label'].'</span>';
            if($atts['suffix_tooltip']!='') $result .= '<span class="super-toggle-suffix-question super-tooltip" title="' . esc_attr( stripslashes( $atts['suffix_tooltip'] ) ) . '"></span>';
            $result .= '</div>';
        }

        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }


    /** 
     *  Slider field
     *
     *  @since      1.2.1
    */    
    public static function slider_field($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        wp_enqueue_style( 'simpleslider', SUPER_PLUGIN_FILE.'assets/css/backend/simpleslider.css', array(), SUPER_VERSION );    
        wp_enqueue_script( 'simpleslider', SUPER_PLUGIN_FILE.'assets/js/backend/simpleslider.js', array( 'jquery' ), SUPER_VERSION, false ); 

        // Get default value
        $atts['value'] = self::get_default_value($tag, $atts, $settings, $entry_data, '0');

        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        $result .= '<input tabindex="-1" class="super-shortcode-field" type="text"';

        $result .= ' name="' . $atts['name'] . '" value="' . esc_attr($atts['value']) . '" data-decimals="' . $atts['decimals'] . '" data-thousand-separator="' . $atts['thousand_separator'] . '" data-decimal-separator="' . $atts['decimal_separator'] . '" data-steps="' . $atts['steps'] . '" data-currency="' . $atts['currency'] . '" data-format="' . $atts['format'] . '"';
        $result .= self::common_attributes( $atts, $tag, $settings );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }

    /** 
     *  Currency field
     *
     *  @since      2.1.0
    */ 
    public static function currency($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation  
        wp_enqueue_script( 'super-masked-currency', SUPER_PLUGIN_FILE . 'assets/js/frontend/masked-currency.js', array( 'jquery' ), SUPER_VERSION, false ); 
        
        if( !isset( $atts['format'] ) ) $atts['format'] = '';
        if( !isset( $atts['currency'] ) ) $atts['currency'] = '$';
        if( !isset( $atts['decimals'] ) ) $atts['decimals'] = 2;
        if( !isset( $atts['thousand_separator'] ) ) $atts['thousand_separator'] = ',';
        if( !isset( $atts['decimal_separator'] ) ) $atts['decimal_separator'] = '.';
        if( $atts['thousand_separator']==$atts['decimal_separator'] ){
            $atts['thousand_separator'] = ''; // Can't be the same, set it to empty value
        }

        // Get default value
        $atts['value'] = self::get_default_value($tag, $atts, $settings, $entry_data);

        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<input tabindex="-1" class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="tel"';
        $result .= ' name="' . $atts['name'] . '" value="' . esc_attr($atts['value']) . '" data-decimals="' . $atts['decimals'] . '" data-thousand-separator="' . $atts['thousand_separator'] . '" data-decimal-separator="' . $atts['decimal_separator'] . '" data-currency="' . $atts['currency'] . '" data-format="' . $atts['format'] . '"';

        // @since 4.2.0 - custom threshold to trigger hooks
        if( !empty($atts['threshold']) ) {
            $result .= ' data-threshold="' . $atts['threshold'] . '"';
        }

        $result .= self::common_attributes( $atts, $tag, $settings );
        $result .= ' />';

        $result .= '<input type="hidden" value="' . esc_attr(str_replace($atts['thousand_separator'], "", $atts['value'])) . '" />';
        
        // @since 4.9.3 - Adaptive placeholders
        $result .= self::adaptivePlaceholders( $settings, $atts, $tag );

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }

    public static function text($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        // Auto suggest and Keywords/tags can't be enabled at the same time
        if( !empty($atts['enable_auto_suggest']) && !empty($atts['enable_keywords']) ) {
            $atts['enable_auto_suggest'] = '';
        }

        // @since 4.7.0 - field types
        if( !isset( $atts['type'] ) ) $atts['type'] = 'text';
        if( empty($atts['step']) ) $atts['step'] == 'any';

        if($atts['type']==='int-phone'){
            wp_enqueue_style( 'super-int-phone', SUPER_PLUGIN_FILE . 'assets/css/frontend/int-phone.css', array(), SUPER_VERSION );    
            wp_enqueue_style( 'super-flags', SUPER_PLUGIN_FILE . 'assets/css/frontend/flags.css', array(), SUPER_VERSION );    
            wp_enqueue_script( 'super-int-phone-utils', SUPER_PLUGIN_FILE . 'assets/js/frontend/int-phone-utils.js', array( 'super-common' ), SUPER_VERSION );
            wp_enqueue_script( 'super-int-phone', SUPER_PLUGIN_FILE . 'assets/js/frontend/int-phone.js', array( 'super-common', 'super-int-phone-utils' ), SUPER_VERSION );
        }

        // Set validation to 'numeric' if field type was set to 'number'
        if($atts['type'] == 'number') $atts['validation'] = 'float';

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
        $address_auto_complete_attr = '';
        $address_auto_populate_class = '';
        if( !isset( $atts['enable_address_auto_complete'] ) ) $atts['enable_address_auto_complete'] = '';
        if( $atts['enable_address_auto_complete']=='true' ) {
            $address_auto_populate_class = ' super-address-autopopulate';
            // @since 4.9.557 - Check if we need to filter by countrie(s) or  return by specific type
            if( !empty( $atts['address_api_types'] ) ) $address_auto_complete_attr .= ' data-types="' . $atts['address_api_types'] . '"';
            if( !empty( $atts['address_api_countries'] ) ) $address_auto_complete_attr .= ' data-countries="' . $atts['address_api_countries'] . '"';
            // Check if we need to auto populate fields with the retrieved data
            if( !isset( $atts['enable_address_auto_populate'] ) ) $atts['enable_address_auto_populate'] = '';
            if( $atts['enable_address_auto_populate']=='true' ) {
                //onFocus="geolocate()"
                foreach($atts['address_auto_populate_mappings'] as $k => $v){
                    if( $v['field']!='' ) $address_auto_complete_attr .= ' data-map-' . $v['key'] . '="' . $v['field'] . '|' . $v['type'] . '"';
                }
            }
            // If API key is empty, try to grab it from global settings
            if(empty($atts['address_api_key'])){
                $global_settings = SUPER_Common::get_global_settings();
                if( !empty($global_settings['form_google_places_api']) ) {
                    $atts['address_api_key'] = $global_settings['form_google_places_api'];
                }
            }
            if(empty($atts['address_api_language'])){
                $global_settings = SUPER_Common::get_global_settings();
                if( !empty($global_settings['google_maps_api_language']) ) {
                    $atts['address_api_language'] = $global_settings['google_maps_api_language'];
                }
            }
            if(empty($atts['address_api_region'])){
                $global_settings = SUPER_Common::get_global_settings();
                if( !empty($global_settings['google_maps_api_region']) ) {
                    $atts['address_api_region'] = $global_settings['google_maps_api_region'];
                }
            }
            $url = '//maps.googleapis.com/maps/api/js?';
            if( !empty( $atts['address_api_key'] ) ) {
                $address_auto_complete_attr .= ' data-api-key="' . $atts['address_api_key'] . '"';
            }
            if( !empty( $atts['address_api_language'] ) ) {
                $address_auto_complete_attr .= ' data-api-language="' . $atts['address_api_language'] . '"';
                $url .= 'language='.$atts['address_api_language'].'&';
            }
            if( !empty( $atts['address_api_region'] ) ) {
                $address_auto_complete_attr .= ' data-api-region="' . $atts['address_api_region'] . '"';
                $url .= 'region='.$atts['address_api_region'].'&';
            }
            $url .= 'key=' . $atts['address_api_key'] . '&libraries=drawing,geometry,places,visualization&callback=SUPER.google_maps_init';
            wp_enqueue_script( 'google-maps-api', $url, array( 'super-common' ), SUPER_VERSION, false );
        }

        // @since   1.2.4 - auto suggest feature
        if( !isset( $atts['enable_auto_suggest'] ) ) $atts['enable_auto_suggest'] = '';
        $class = ($atts['enable_auto_suggest']=='true' ? ' super-auto-suggest' : '');

        // @since   4.6.0 - wc order search
        if( !isset( $atts['wc_order_search'] ) ) $atts['wc_order_search'] = '';
        $class .= ($atts['wc_order_search']=='true' ? ' super-wc-order-search' : '');

        // @since   3.7.0 - keyword tags
        if( !empty($atts['enable_keywords']) ) {
            $class .= ' super-keyword-tags';
        }

        // @since   3.1.0 - uppercase transformation
        if( !isset( $atts['uppercase'] ) ) $atts['uppercase'] = '';
        $class .= ($atts['uppercase']=='true' ? ' super-uppercase' : '');

        // @since 5.0.100 - international phonenumber
        if($atts['type']==='int-phone'){
            $class .= ' super-int-phone-field';
        }
        
        // Get default value
        $atts['value'] = self::get_default_value($tag, $atts, $settings, $entry_data);
        $result = self::opening_tag( $tag, $atts, $class );

        $wrapper_class = '';
        if( ($atts['enable_auto_suggest']=='true') && (!empty($entry_data[$atts['name']])) && (!empty($entry_data[$atts['name']]['value'])) ) {
            // Check if value exist in one of the items
            // If so add overlap class, otherwise don't and check if user is allowed to enter none-existing values (manual input)
            $get_items = self::get_items(array('items'=>array(), 'tag'=>$tag, 'atts'=>$atts, 'prefix'=>'', 'settings'=>$settings, 'entry_data'=>$entry_data));
            $firstItemValues = array();
            foreach($get_items['items_values'] as $v){
                $firstValue = explode(';', $v)[0];
                $firstItemValues[] = $firstValue;
            }
            if(in_array($entry_data[$atts['name']]['value'], $firstItemValues, true )){
                $wrapper_class = 'super-overlap'; 
            }
        }

        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings, $wrapper_class );
        
        // @since 2.9.0 - keyword enabled
        if( !isset( $atts['enable_keywords'] ) ) $atts['enable_keywords'] = '';
        if( !isset( $atts['keyword_split_method'] ) ) $atts['keyword_split_method'] = 'both';

        $result .= '<input tabindex="-1" class="super-shortcode-field';
        $result .= $distance_calculator_class;
        $result .= $address_auto_populate_class; 
        if( !empty($atts['class']) ) {
            $result .= ' ' . $atts['class'];
        }

        // @since 4.7.0 - field types
        $result .= '" type="' . $atts['type'] . '"';
        if( ($atts['type']=='number') && (!empty($atts['step'])) ) {
            $result .= '" step="' . $atts['step'] . '"';
        }
        if( $atts['enable_distance_calculator']=='true' ) {
            $result .= $data_attributes;
        }

        if( $atts['enable_auto_suggest']=='true' ) {
            $get_items = self::get_items(array('items'=>array(), 'tag'=>$tag, 'atts'=>$atts, 'prefix'=>'', 'settings'=>$settings, 'entry_data'=>$entry_data));
            $items = $get_items['items'];
            $atts = $get_items['atts'];
        }

        $result .= ' name="' . $atts['name'] . '"';

        if( $atts['value']!=='' ) {
            $result .= ' value="' . esc_attr($atts['value']) . '"';
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

            // @since 3.1.0
            // make sure if the parameter of this field element is set in the POST or GET we 
            // have to set the GET variables to auto fill the form fields based on the contact entry found
            if( $atts['value']!='' ) {
                global $wpdb;
                $value = sanitize_text_field($atts['value']);
                $method = sanitize_text_field($atts['search_method']);
                $table = $wpdb->prefix . 'posts';
                $table_meta = $wpdb->prefix . 'postmeta';
                if($method=='equals') $query = "post_title = BINARY '$value'";
                if($method=='contains') $query = "post_title LIKE BINARY '%$value%'";
                $entry = $wpdb->get_row("SELECT ID FROM $table WHERE $query AND post_status IN ('publish','super_unread','super_read') AND post_type = 'super_contact_entry'");
                if($entry){
                    $data = get_post_meta( $entry->ID, '_super_contact_entry_data', true );
                    unset($data['hidden_form_id']);
                    $skip_fields = explode( "|", $skip );
                    foreach($skip_fields as $field_name){
                        if( isset($data[$field_name]) ) {
                            unset($data[$field_name]);
                        }
                    }
                    $data['hidden_contact_entry_id'] = array(
                        'name' => 'hidden_contact_entry_id',
                        'value' => $entry->ID,
                        'type' => 'entry_id'
                    );
                    if (is_array($data) || is_object($data)) {
                        foreach($data as $k => $v){
                            $_GET[$k] = $v['value'];
                        }
                    }

                }
            }
        }
        if( $atts['wc_order_search']=='true' ) {
            if(!empty($atts['wc_order_search_method'])) $result .= ' data-wcosm="' . esc_attr($atts['wc_order_search_method']) . '"';
            if(!empty($atts['wc_order_search_filterby'])) $result .= ' data-wcosfb="' . implode(';',explode("\n",$atts['wc_order_search_filterby'])) . '"';
            if(!empty($atts['wc_order_search_return_label'])) $result .= ' data-wcosrl="' . esc_attr($atts['wc_order_search_return_label']) . '"';
            if(!empty($atts['wc_order_search_return_value'])) $result .= ' data-wcosrv="' . esc_attr($atts['wc_order_search_return_value']) . '"';
            if(!empty($atts['wc_order_search_populate'])) $result .= ' data-wcosp="' . esc_attr($atts['wc_order_search_populate']) . '"';
            if(!empty($atts['wc_order_search_skip'])) $result .= ' data-wcoss="' . esc_attr($atts['wc_order_search_skip']) . '"';
            if(!empty($atts['wc_order_search_status'])) $result .= ' data-wcosst="' . implode(';',explode("\n",$atts['wc_order_search_status'])) . '"';
            if(!empty($atts['value'])) {
                $value = sanitize_text_field($atts['value']);
                $method = $atts['wc_order_search_method'];
                $query = '';
                if($method=='equals') {
                    $query .= "(wc_order.ID LIKE '$value') OR ";
                }
                if($method=='contains') {
                    $query .= "(wc_order.ID LIKE '%$value%') OR ";
                }
                global $wpdb;
                $filterby = explode(";", $atts['wc_order_search_filterby']);
                foreach($filterby as $k => $v){
                    if(!empty($v)){
                        if($k>0){
                            $query .= " OR ";
                        }
                        if($method=='equals') {
                            $query .= "(meta.meta_key = '".$v."' AND meta.meta_value LIKE '$value')";
                        }
                        if($method=='contains') {
                            $query .= "(meta.meta_key = '".$v."' AND meta.meta_value LIKE '%$value%')";
                        }
                    }
                }

                $order = $wpdb->get_row("SELECT ID
                    FROM $wpdb->posts AS wc_order
                    INNER JOIN $wpdb->postmeta AS meta ON meta.post_id = wc_order.ID
                    WHERE $query
                    AND post_type = 'shop_order'
                    LIMIT 1");
                if($order){
                    if(!empty($atts['wc_order_search_populate'])){
                        $data = SUPER_Common::get_entry_data_by_wc_order_id($order->ID, $atts['wc_order_search_skip']);
                        if(is_array($data)){
                            foreach($data as $k => $v){
                                if(isset($v['value'])){
                                    $_GET[$k] = $v['value'];
                                }
                            }
                        }                        
                    }
                }
            }
        }
        
        // @since 3.0.0 - add data attributes to map google places data to specific fields
        // @since 4.9.557 - Check if we need to display in specific language, filter by countrie(s), return by specific type
        if( !empty($address_auto_complete_attr) ) $result .= $address_auto_complete_attr;
        
        // @since 3.6.0 - disable autocomplete when either auto suggest or keyword functionality is enabled
        if( !empty($atts['enable_auto_suggest']) ) {
            $atts['autocomplete'] = 'true';
            // Filter logic (used by autosuggest or keywords)
            $result .= ' data-logic="' . esc_attr($atts['filter_logic']) . '"';
        }
        if( !empty($atts['enable_keywords']) ) {
            $atts['autocomplete'] = 'true';
            // Filter logic (used by autosuggest or keywords)
            $result .= ' data-logic="' . esc_attr($atts['keywords_filter_logic']) . '"';
        }

        $result .= self::common_attributes( $atts, $tag, $settings );
        $result .= ' />';
        
        // @since 4.9.3 - Adaptive placeholders
        $result .= self::adaptivePlaceholders( $settings, $atts, $tag );

        // @since 2.9.0 - entered keywords
        if( !empty($atts['enable_keywords']) ) {
            
            $get_items = self::get_items(array('items'=>array(), 'tag'=>$tag, 'atts'=>$atts, 'prefix'=>'keywords_', 'settings'=>$settings, 'entry_data'=>$entry_data));
            $items = $get_items['items'];
            $atts = $get_items['atts'];

            $result .= '<div class="super-autosuggest-tags">';
                //$result .= '<div>';
                // Populate form with data
                if(!empty($atts['value'])){
                    $tags = explode(',', $atts['value']);
                    if( empty( $items) ) { // User can input his own tags, no predefined items here
                        foreach( $tags as $tag ) {
                            $result .= '<span class="super-noselect super-keyword-tag" sfevents=\'{"click":"keywords.remove"}\' data-value="' . esc_attr($tag) . '" title="remove this tag"' . SUPER_Common::get_tags_attributes($tag) . '><span' . SUPER_Common::get_tags_attributes($tag) . '>' . ($tag) . '</span></span>';
                        }
                    }else{
                        foreach( $atts['keywords_items'] as $tag ) {
                            if(in_array($tag['value'], $tags, true )) {
                                $result .= '<span class="super-noselect super-keyword-tag" sfevents=\'{"click":"keywords.remove"}\' data-value="' . esc_attr($tag['value']) . '" title="remove this tag"' . SUPER_Common::get_tags_attributes($tag['value']) . '><span' . SUPER_Common::get_tags_attributes($tag['label']) . '>' . ($tag['label']) . '</span></span>';
                            }
                        }
                    }
                }
                //$result .= '</div>';
                if( empty($atts['keywords_retrieve_method']) ) $atts['keywords_retrieve_method'] = 'free';
                $result .= '<input tabindex="-1" class="super-keyword-filter" type="text"';
                $result .= self::common_attributes( $atts, $tag, $settings );
                $result .= ' data-method="' . esc_attr($atts['keywords_retrieve_method']) . '"';
                $result .= ' data-split-method="' . esc_attr($atts['keyword_split_method']) . '"';
                $result .= ' sfevents="' . esc_attr('{"onblur":"unfocusField","keyup,keydown":"keywords.filter"}') . '"';
                $result .= ' />';
            $result .= '</div>';

            $result .= '<ul class="super-dropdown-list super-autosuggest-tags-list">';
            $result .= '<li data-value="" data-search-value="" class="super-no-results">' . esc_html__( 'No matches found', 'super-forms' ) . '...</li>';
            foreach( $items as $k => $v ) {
                $result .= $v;
            }
            $result .= '</ul>';
        }else{
            // @since 1.2.4
            if( $atts['enable_auto_suggest']=='true' ) {
                $result .= '<ul class="super-dropdown-list">';
                foreach( $items as $v ) {
                    $result .= $v;
                }
                $result .= '</ul>';
            }
        }

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';

        if( ($atts['enable_address_auto_complete']=='true') && (empty($atts['address_api_key'])) ) {
            $result .= '<strong style="color:red;">' . esc_html__( 'Please edit this field and enter your "Google API key" under the "Address auto complete" TAB', 'super-forms' ) . '</strong>';
        }

        $result .= self::loop_conditions( $atts, $tag );
        $result .= self::loop_variable_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function textarea($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation
        
        // Get default value
        $atts['value'] = self::get_default_value($tag, $atts, $settings, $entry_data);

        $result  = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

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
                    if ( file_exists( "{$abspath_inc}/css/$v.css" ) ) {
                        if(!isset($wp_styles)){
                            $style_content .= wp_remote_fopen("{$includes_url}css/$v.css");
                        }else{
                            if( !in_array( $k, $wp_styles->queue, true ) ) $style_content .= wp_remote_fopen("{$includes_url}css/$v.css");
                        }
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
                    if ( file_exists( "{$abspath_inc}/js/$v.js" ) ) {
                        if( !in_array( $k, $wp_scripts->queue, true ) ) $result .= "<script type='text/javascript' src='{$includes_url}js/$v.js?$version'></script>";
                    }
                }
                $baseurl = includes_url();
                $baseurl_tinymce = includes_url( 'js/tinymce' );
                $mce_suffix = false !== strpos( $wp_version, '-src' ) ? '' : '.min';
                $compressed = $compress_scripts && $concatenate_scripts && isset($_SERVER['HTTP_ACCEPT_ENCODING'])
                    && false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip');
                if ( $compressed ) {
                    $result .= "<script type='text/javascript' src='{$baseurl_tinymce}/wp-tinymce.php?c=1&amp;$version'></script>\n";
                    $result .= "<script type='text/javascript' src='{$baseurl}js/utils.js?$version'></script>\n";
                } else {
                    $result .= "<script type='text/javascript' src='{$baseurl_tinymce}/tinymce{$mce_suffix}.js?$version'></script>\n";
                    $result .= "<script type='text/javascript' src='{$baseurl_tinymce}/plugins/compat3x/plugin.js?$version'></script>\n";
                    $result .= "<script type='text/javascript' src='{$baseurl}js/utils.js?$version'></script>\n";
                }
                $abspath_inc = ABSPATH . 'wp-admin';
                $array = array(
                    'editor' => 'editor',
                    'media-upload' => 'media-upload'
                );
                foreach( $array as $k => $v ) {
                    if ( file_exists( "{$abspath_inc}/js/$v.js" ) ) {
                        if( !in_array( $k, $wp_scripts->queue, true ) ) $result .= "<script type='text/javascript' src='{$admin_url}js/$v.js?$version'></script>";
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
                $common_attributes = self::common_attributes( $atts, $tag, $settings );
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
                $common_attributes = self::common_attributes( $atts, $tag, $settings );
                $editor_html = str_replace( '<textarea','<textarea '.$common_attributes.' ', $editor_html );
                $editor_html = str_replace( '<textarea', '<textarea id="' . $atts['name'] . '-' . self::$current_form_id . '"', $editor_html );
                $result .= str_replace( 'super-shortcode-field', 'super-shortcode-field super-text-editor super-initialized', $editor_html );
            }
        }else{

            // @since 1.9 - custom class
            if( !isset( $atts['class'] ) ) $atts['class'] = '';

            $result .= '<textarea tabindex="-1" class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"';
            $result .= ' name="' . $atts['name'] . '"';

            if( $atts['height']>0 ) {
                $result .= ' style="min-height:' . $atts['height'] . 'px;" ';
            }
            $result .= self::common_attributes( $atts, $tag, $settings );

            // @since 3.6.0 - convert <br /> tags to \n
            $value = preg_replace('#<br\s*/?>#i', "\n", $atts['value']);
            $result .= ' >' . $value . '</textarea>';

            // @since 4.9.3 - Adaptive placeholders
            $result .= self::adaptivePlaceholders( $settings, $atts, $tag );

        }

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }
    public static function dropdown($x) {
        extract(self::extract($x));
        global $woocommerce;
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation
        $placeholder = (!empty($atts['placeholder']) ? $atts['placeholder'] : '');
        $placeholderFilled = (!empty($atts['placeholderFilled']) ? $atts['placeholderFilled'] : '');

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

        // Get default value
        $atts['value'] = self::get_default_value($tag, $atts, $settings, $entry_data);
        
        // @since   4.7.7 - make sure we do not lose the default placeholder
        // This is required for dynamic columns
        $atts['default_placeholder'] = $atts['placeholder'];
        $get_items = self::get_items(array('items'=>array(), 'tag'=>$tag, 'atts'=>$atts, 'prefix'=>'', 'settings'=>$settings, 'entry_data'=>$entry_data));
        if(empty($atts['language_switch'])){
            $items = $get_items['items'];
        }else{
            $items = $atts['dropdown_items'];
        }
        $atts = $get_items['atts'];

        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        
        $multiple = '';
        if( !isset( $atts['maxlength'] ) ) $atts['maxlength'] = 0;
        if( !isset( $atts['minlength'] ) ) $atts['minlength'] = 0;
        if( ($atts['minlength']>1) || ($atts['maxlength']>1) ) $multiple = ' multiple';

        if(empty($atts['language_switch'])){
            $result .= '<input type="hidden" class="super-shortcode-field';
            $result .= $distance_calculator_class;
            $result .= ($atts['class']!='' ? ' ' . $atts['class'] : '');
            $result .= '"';
            $result .= ($atts['enable_distance_calculator']=='true' ? $data_attributes : '');
            $result .= ' name="' . esc_attr( $atts['name'] ) . '" value="' . esc_attr($atts['value']) . '"';
            $result .= self::common_attributes( $atts, $tag, $settings );
            $result .= ' />';
        }

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        // @since 1.2.8     - auto scroll to value after key press
        if(empty($atts['disable_filter'])){
            $result .= '<input tabindex="-1" type="text" name="super-dropdown-search" value="" data-logic="' . esc_attr($atts['filter_logic']) . '" />';
        }

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<ul class="super-dropdown-list' . $multiple . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '">';
        $item_placeholder = $atts['placeholder'];
        if(!empty($get_items['atts']['placeholder'])) {
            $item_placeholder = $get_items['atts']['placeholder'];
        }
        
        if(!empty($atts['language_switch'])){
            $result .= '<li data-value="" class="super-item super-placeholder">';
            $result .= '<img src="'. esc_url(SUPER_PLUGIN_FILE . 'assets/images/blank.gif') . '" class="flag flag-' . $atts['default_language']['flag'] . '" />';
            $result .= '<span>' . $atts['default_language']['language'] . '</span>';
            $result .= '</li>';
        }else{
            $result .= '<li data-value="" class="super-item super-placeholder">' . $item_placeholder . '</li>';
        }
        foreach( $items as $v ) {
            if(!empty($atts['language_switch'])){
                $result .= '<li class="super-item' . ($v['flag']==$atts['default_language']['flag'] ? ' super-active' : '') . '" data-value="' . esc_attr($v['value']) . '">';
                $result .= '<img src="'. esc_url(SUPER_PLUGIN_FILE . 'assets/images/blank.gif') . '" class="flag flag-' . $v['flag'] . '" />';
                $result .= '<span>' . $v['language'] . '</span>';
                $result .= '</li>';
            }else{
                $result .= $v;
            }
        }
        $result .= '</ul>';
        $result .= '<span class="super-dropdown-arrow"><span class="super-after"><i class="fas fa-caret-down"></i></span></span>';
        // @since 4.9.3 - Adaptive placeholders
        $atts['placeholder'] = $placeholder;
        $atts['placeholderFilled'] = $placeholderFilled;
        $result .= self::adaptivePlaceholders( $settings, $atts, $tag );
        $result .= '</div>';
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;    
    }
    public static function checkbox($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        // @since 4.7.7 - new radio and checkbox layout options
        $layout = $atts['display'];
        $classes = ' display-' . str_replace('_', '-', $layout);
        if($layout=='grid'){
            $classes .= ' super-c-' . $atts['display_columns'];
        }

        // Get default value
        $atts['value'] = self::get_default_value($tag, $atts, $settings, $entry_data);

        $result = self::opening_tag( $tag, $atts, $classes );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $get_items = self::get_items(array('items'=>array(), 'tag'=>$tag, 'atts'=>$atts, 'prefix'=>'', 'settings'=>$settings, 'entry_data'=>$entry_data));
        $items = $get_items['items'];
        $atts = $get_items['atts'];
        $selected_items = explode( ",", $atts['value'] );

        if($layout=='grid'){
            $max_columns = (isset($atts['display_columns']) ? absint($atts['display_columns']) : 4);
            $max_rows = (isset($atts['display_rows']) ? absint($atts['display_rows']) : 3);
            $columns = 0;
            $rows = 0;
            if( (empty($atts['display'])) || (!empty($atts['display']) && $atts['display']!='slider') ){
                $result .= '<div class="super-items-list">';
            }
            foreach( $items as $v ) {
                if($rows>=$max_rows && $max_rows!=0){
                    $columns++; // 1
                    if($columns>=$max_columns){
                        $columns = 0;
                        $rows++;
                    }
                }else{
                    $result .= $v;
                    $columns++; // 1
                    if($columns>=$max_columns){
                        $columns = 0;
                        $rows++;
                    }
                }
            }
            if( (empty($atts['display'])) || (!empty($atts['display']) && $atts['display']!='slider') ){
                $result .= '</div>';
            }

        }else{
            // Check if this the "Slider" layout is enabled, if so we will add a wrapper so that the "CarouselJS" can initilize the slider/carousel
            if(!empty($atts['display']) && $atts['display']=='slider'){
                wp_enqueue_style( 'super-carousel', SUPER_PLUGIN_FILE.'assets/css/frontend/carousel.css', array(), SUPER_VERSION );    
                wp_enqueue_script( 'super-carousel', SUPER_PLUGIN_FILE . 'assets/js/frontend/carousel.js', array( 'super-common' ), SUPER_VERSION );
                $result .= '<div class="carouseljs">';
                // Override default configuration for the carousel based on element settings
                $result .= '<textarea>{"columns":"' . absint($atts['display_columns']) . '","minwidth":"' . absint($atts['display_minwidth']) . '","navigation":' . ($atts['display_nav']===true ? 'true' : 'false') . ',"dots":' . ($atts['display_dots_nav']===true ? 'true' : 'false') . '}</textarea>';
            }
            if( (empty($atts['display'])) || (!empty($atts['display']) && $atts['display']!='slider') ){
                $result .= '<div class="super-items-list">';
            }
            foreach( $items as $v ) {
                $result .= $v;
            }
            if( (empty($atts['display'])) || (!empty($atts['display']) && $atts['display']!='slider') ){
                $result .= '</div>';
            }
            if(!empty($atts['display']) && $atts['display']=='slider'){
                $result .= '</div>';
            }
        }

        $result .= '<input class="super-shortcode-field" type="hidden"';
        $result .= ' name="' . esc_attr( $atts['name'] ) . '" value="' . esc_attr(implode(',',$selected_items)) . '"';
        $result .= self::common_attributes( $atts, $tag, $settings );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }
    public static function radio($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation
       
        // @since 4.7.7 - new radio and checkbox layout options
        $layout = $atts['display'];
        $classes = ' display-' . str_replace('_', '-', $layout);
        if($layout=='grid'){
            $classes .= ' super-c-' . $atts['display_columns'];
        }

        // Get default value
        $atts['value'] = self::get_default_value($tag, $atts, $settings, $entry_data);

        $result = self::opening_tag( $tag, $atts, $classes );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';     

        $get_items = self::get_items(array('items'=>array(), 'tag'=>$tag, 'atts'=>$atts, 'prefix'=>'', 'settings'=>$settings, 'entry_data'=>$entry_data));
        $items = $get_items['items'];
        $atts = $get_items['atts'];

        if($layout=='grid'){
            $max_columns = (isset($atts['display_columns']) ? absint($atts['display_columns']) : 4);
            $max_rows = (isset($atts['display_rows']) ? absint($atts['display_rows']) : 3);
            $columns = 0;
            $rows = 0;
            if( (empty($atts['display'])) || (!empty($atts['display']) && $atts['display']!='slider') ){
                $result .= '<div class="super-items-list">';
            }
            foreach( $items as $v ) {
                if($rows>=$max_rows && $max_rows!=0){
                    $columns++; // 1
                    if($columns>=$max_columns){
                        $columns = 0;
                        $rows++;
                    }
                }else{
                    $result .= $v;
                    $columns++; // 1
                    if($columns>=$max_columns){
                        $columns = 0;
                        //$result .= '<div class="super-line-break"></div>';
                        $rows++;
                    }
                }
            }
            if( (empty($atts['display'])) || (!empty($atts['display']) && $atts['display']!='slider') ){
                $result .= '</div>';
            }

        }else{
            // Check if this the "Slider" layout is enabled, if so we will add a wrapper so that the "CarouselJS" can initilize the slider/carousel
            if(!empty($atts['display']) && $atts['display']=='slider'){
                wp_enqueue_style( 'super-carousel', SUPER_PLUGIN_FILE.'assets/css/frontend/carousel.css', array(), SUPER_VERSION );    
                wp_enqueue_script( 'super-carousel', SUPER_PLUGIN_FILE . 'assets/js/frontend/carousel.js', array( 'super-common' ), SUPER_VERSION );
                $result .= '<div class="carouseljs">';
                // Override default configuration for the carousel based on element settings
                $result .= '<textarea>{"columns":"' . absint($atts['display_columns']) . '","minwidth":"' . absint($atts['display_minwidth']) . '","navigation":' . ($atts['display_nav']===true ? 'true' : 'false') . ',"dots":' . ($atts['display_dots_nav']===true ? 'true' : 'false') . '}</textarea>';
            }
            if( (empty($atts['display'])) || (!empty($atts['display']) && $atts['display']!='slider') ){
                $result .= '<div class="super-items-list">';
            }
            foreach( $items as $v ) {
                $result .= $v;
            }
            if( (empty($atts['display'])) || (!empty($atts['display']) && $atts['display']!='slider') ){
                $result .= '</div>';
            }
            if(!empty($atts['display']) && $atts['display']=='slider'){
                $result .= '</div>';
            }
        }

        $result .= '<input class="super-shortcode-field" type="hidden"';
        $result .= ' name="' . esc_attr( $atts['name'] ) . '" value="' . esc_attr($atts['value']) . '"';
        $result .= self::common_attributes( $atts, $tag, $settings );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }
    public static function file($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        $dir = SUPER_PLUGIN_FILE . 'assets/js/frontend/jquery-file-upload/';
        wp_enqueue_script( 'jquery-ui-widget' );
        wp_enqueue_script( 'jquery-iframe-transport', $dir . 'jquery.iframe-transport.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
        wp_enqueue_script( 'jquery-fileupload', $dir . 'jquery.fileupload.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
        wp_enqueue_script( 'jquery-fileupload-process', $dir . 'jquery.fileupload-process.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
        wp_enqueue_script( 'jquery-fileupload-validate', $dir . 'jquery.fileupload-validate.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
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
        $result .= '>';

        // @since 1.2.8
        if( ($atts['enable_image_button']=='true') && ($atts['image']!='') ) {
            if( !isset( $atts['image'] ) ) $atts['image'] = 0;
            $attachment_id = absint($atts['image']);
            if( $attachment_id===0 ) {
                $url = SUPER_PLUGIN_FILE . 'assets/images/image-icon.png';
            }else{
                $title = get_the_title( $attachment_id );
                $url = wp_get_attachment_url( $attachment_id );
                $alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
            } 
            $img_styles = '';
            if( !isset( $atts['max_img_width'] ) ) $atts['max_img_width'] = 0;
            if( !isset( $atts['max_img_height'] ) ) $atts['max_img_height'] = 0;
            if( $atts['max_img_width']>0 ) {
                $img_styles .= 'max-width:' . $atts['max_img_width'] . 'px;';
            }
            if( $atts['max_img_height']>0 ) {
                $img_styles .= 'max-height:' . $atts['max_img_height'] . 'px;';
            }
            if($img_styles!='') $img_styles = 'style="' . esc_attr($img_styles) . '" ';
            $result .= '<img src="' . esc_url($url) . '" ' . $img_styles . 'alt="' . esc_attr($alt) . '" title="' . esc_attr($title) . '" />';
        }else{
            $result .= '<i class="fas fa-plus"></i><span class="super-fileupload-button-text"' . SUPER_Common::get_tags_attributes($atts['placeholder']) . '>' . ($atts['placeholder']) . '</span>';
        }

        $result .= '</div>';
        $atts['placeholder'] = '';

        $files = '';
        // @since   2.9.0 - autopopulate with last entry data
        if( ($entry_data!=null) && (isset($entry_data[$atts['name']])) ) {
            if(isset($entry_data[$atts['name']]['files'])) {
                foreach( $entry_data[$atts['name']]['files'] as $k => $v ) {
                    if( isset($v['url']) ) {
                        // Before adding the file, check if the file still exists.
                        // In some cases this might not be the case due to the file being deleted from the server manually
                        // or when the setting "Delete files from server after form submissions" is enabled
                        // If file has attachment ID, always try to retrieve the url via WP core function
                        if(!empty($v['attachment'])){
                            $v['url'] = wp_get_attachment_url($v['attachment']);
                        }
                        $file = $v['url'];
                        $fileType = (!empty($v['type']) ? $v['type'] : '');
                        $basename = basename($file);
                        $file_headers = @get_headers($file);
                        if($file_headers && (strpos($file_headers[0], '404'))===false) {
                            // File exists, let's add it to the list
                            $files .= '<div class="super-uploaded" data-name="' . esc_attr($basename) . '" title="' . esc_attr($basename) . '" data-type="' . esc_attr($fileType) . '" data-url="' . esc_url($file) . '">';
                                if(!empty($fileType) && (strpos($fileType, 'image/'))===0){
                                    $files .= '<span class="super-fileupload-image super-file-type-' . esc_attr(str_replace('/', '-', $fileType)) . '">';
                                        $files .= '<img src="' . esc_url($file) . '">';
                                    $files .= '</span>';
                                }else{
                                    $files .= '<span class="super-fileupload-document super-file-type-' . esc_attr(str_replace('/', '-', $fileType)) . '"></span>';
                                }
                                $files .= '<span class="super-fileupload-info">';
                                    $split = explode('.', $basename);
                                    $filename = $split[0];
                                    $ext = $split[1];
                                    if (strlen($filename) > 10) $filename = substr($filename, 0, 10).'...';
                                    $files .= '<span class="super-fileupload-name">' . esc_html($filename) . '.' . $ext . '</span>';
                                    $files .= '<span class="super-fileupload-delete"></span>';
                                $files .= '</span>';
                            $files .= '</div>';
                        }
                    }
                }
            }
        }
        if( !isset( $atts['maxlength'] ) ) $atts['maxlength'] = 0;
        if( !isset( $atts['minlength'] ) ) $atts['minlength'] = 0;

        $result .= '<input tabindex="-1" class="super-shortcode-field super-fileupload" type="file" name="files[]" ';
        $result .= ' data-file-size="' . $atts['filesize'] . '"';
        $singleFileSizeMax = absint($atts['filesize']);
        $totalFilesAllowed = absint($atts['maxlength']);
        if($totalFilesAllowed===0){
            $combinedUploadLimit = $singleFileSizeMax;
        }else{
            $combinedUploadLimit = absint($singleFileSizeMax*$totalFilesAllowed);
        } 
        $result .= ' data-upload-limit="' . $combinedUploadLimit . '"';
        $result .= ' data-accept-file-types="' . $extensions . '"';
        if( ($atts['minlength']>1) || ($atts['maxlength']>1) ) $result .= ' multiple';
        $result .= ' />';
        $result .= '<input class="super-active-files" type="hidden" value="" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag, $settings );
        $result .= ' />';
        $result .= '<div class="super-progress-bar"></div>';
        $result .= '<div class="super-fileupload-files">';
            $result .= $files;
        $result .= '</div>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }
    public static function date($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation
        
        wp_enqueue_script( 'jquery-ui-datepicker', false, array( 'jquery' ), SUPER_VERSION );
        wp_enqueue_script( 'date-format', SUPER_PLUGIN_FILE . 'assets/js/frontend/date-format.js' );
        // @since 4.9.3 - datepicker localizations
        if(empty($atts['localization'])) $atts['localization'] = '';
        if(!empty($atts['localization'])){
            wp_enqueue_script( 'jquery-ui-datepicker-' . $atts['localization'], SUPER_PLUGIN_FILE . 'assets/js/frontend/datepicker/i18n/datepicker-' . $atts['localization'] . '.js', array(), SUPER_VERSION );
        }
        wp_enqueue_script( 'jquery-ui-multidatespicker', SUPER_PLUGIN_FILE . 'assets/js/frontend/datepicker/jquery-ui.multidatespicker.js', array('jquery-ui-datepicker'), SUPER_VERSION );

        if( !isset( $atts['value'] ) ) $atts['value'] = '';

        // @since 1.3 - Return the current date as default value 
        $format = $atts['format'];
        if( $format=='custom' ) $format = $atts['custom_format'];
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
            $atts['absolute_default'] = $atts['value'];
        }

        // Get default value
        $atts['value'] = self::get_default_value($tag, $atts, $settings, $entry_data);

        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<input tabindex="-1" class="super-shortcode-field super-datepicker' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="text" autocomplete="false" ';


        // @since 1.1.8 - added option to select an other datepicker to achieve date range with 2 datepickers (useful for booking forms)
        if( !isset( $atts['connected_min'] ) ) $atts['connected_min'] = '';
        if( !isset( $atts['connected_max'] ) ) $atts['connected_max'] = '';

        // @since 1.2.5 - added option to add or deduct days based on connected datepicker
        if( !isset( $atts['connected_min_days'] ) ) $atts['connected_min_days'] = '1';
        if( !isset( $atts['connected_max_days'] ) ) $atts['connected_max_days'] = '1';

        if( !isset( $atts['range'] ) ) $atts['range'] = '-100:+5';
        if( !isset( $atts['first_day'] ) ) $atts['first_day'] = '1'; // @since 3.1.0 - start day of the week


        // Javascript date format parameters:
        // yy = short year
        // yyyy = long year
        // M = month (1-12)
        // MM = month (01-12)
        // MMM = month abbreviation (Jan, Feb ... Dec)
        // MMMM = long month (January, February ... December)
        // d = day (1 - 31)
        // dd = day (01 - 31)
        // ddd = day of the week in words (Monday, Tuesday ... Sunday)
        // E = short day of the week in words (Mon, Tue ... Sun)
        // D - Ordinal day (1st, 2nd, 3rd, 21st, 22nd, 23rd, 31st, 4th...)
        // h = hour in am/pm (0-12)
        // hh = hour in am/pm (00-12)
        // H = hour in day (0-23)
        // HH = hour in day (00-23)
        // mm = minute
        // ss = second
        // SSS = milliseconds
        // a = AM/PM marker
        // p = a.m./p.m. marker

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

        if(!isset($atts['changeMonth'])) $atts['changeMonth'] = 'true';
        if(!isset($atts['changeYear'])) $atts['changeYear'] = 'true';
        if(!isset($atts['showMonthAfterYear'])) $atts['showMonthAfterYear'] = 'false';
        if(!isset($atts['showWeek'])) $atts['showWeek'] = 'true';
        if(!isset($atts['numberOfMonths'])) $atts['numberOfMonths'] = '1';
        if(!isset($atts['showOtherMonths'])) $atts['showOtherMonths'] = 'false';
        if(!isset($atts['selectOtherMonths'])) $atts['selectOtherMonths'] = 'false';
        // Must be set `showOtherMonths` to false if `showOtherMonths` is not enabled
        if($atts['showOtherMonths']!='true') $atts['selectOtherMonths'] = 'false'; 

        $result .= ' value="' . esc_attr($atts['value']) . '" 
        name="' . esc_attr($atts['name']) . '" 
        data-format="' . esc_attr($format) . '" 
        data-jsformat="' . esc_attr($jsformat) . '" 
        data-connected-min="' . esc_attr($atts['connected_min']) . '" 
        data-connected-min-days="' . esc_attr($atts['connected_min_days']) . '" 
        data-connected-max="' . esc_attr($atts['connected_max']) . '" 
        data-connected-max-days="' . esc_attr($atts['connected_max_days']) . '" 
        data-range="' . esc_attr($atts['range']) . '" 
        data-first-day="' . esc_attr($atts['first_day']) . '"
        data-localization="' . esc_attr($atts['localization']) . '"
        data-change-month="' . esc_attr($atts['changeMonth']) . '"
        data-change-year="' . esc_attr($atts['changeYear']) . '"
        data-show-month-after-year="' . esc_attr($atts['showMonthAfterYear']) . '"
        data-show-week="' . esc_attr($atts['showWeek']) . '"
        data-number-of-months="' . esc_attr($atts['numberOfMonths']) . '"
        data-show-other-months="' . esc_attr($atts['showOtherMonths']) . '"
        data-select-other-months="' . esc_attr($atts['selectOtherMonths']) . '" ';

        // @since 1.5.0 - Allow work days selection
        if( !empty($atts['work_days']) ) {
            $result .= 'data-work-days="true"';
        }
        // @since 1.5.0 - Allow weekend selection
        if( !empty($atts['weekends']) ) {
            $result .= 'data-weekends="true"';
        }
        // @since 4.9.5 - Allow user to choose multiple dates
        if( !empty($atts['maxPicks']) ) {
            $result .= 'data-maxPicks="' . esc_attr($atts['maxPicks']) . '"';
        }
        if( !empty($atts['minPicks']) ) {
            $result .= 'data-minPicks="' . esc_attr($atts['minPicks']) . '"';
        }
        // @since 3.6.0 - Exclude specific days
        if( isset($atts['excl_days']) && $atts['excl_days']!='' ) {
            $result .= 'data-excl-days="' . esc_attr($atts['excl_days']) . '"';
        }
        // @since 4.9.46 - Override days exclusion
        if( isset($atts['excl_days_override']) && $atts['excl_days_override']!='' ) {
            $result .= 'data-excl-days-override="' . esc_attr($atts['excl_days_override']) . '"';
        }
        // @since 4.9.3 - Exclude specific dates
        if( isset($atts['excl_dates']) && $atts['excl_dates']!='' ) {
            $result .= 'data-excl-dates="' . esc_attr($atts['excl_dates']) . '"';
        }
        $result .= self::common_attributes( $atts, $tag, $settings );
        $result .= ' readonly="true" />';

        // @since 4.9.3 - Adaptive placeholders
        $result .= self::adaptivePlaceholders( $settings, $atts, $tag );

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }
    public static function time($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        wp_enqueue_script( 'jquery-timepicker', SUPER_PLUGIN_FILE . 'assets/js/frontend/timepicker.js', array( 'jquery' ), SUPER_VERSION, false );

        // @since 1.3 - Return the current date as default value
        if( !isset( $atts['current_time'] ) ) $atts['current_time'] = '';
        if( $atts['current_time']=='true' ) {
            $atts['value'] = current_time($atts['format']);
        }

        // Get default value
        $atts['value'] = self::get_default_value($tag, $atts, $settings, $entry_data);        
        
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<input tabindex="-1" class="super-shortcode-field super-timepicker' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="text" autocomplete="false" ';
        if( !isset( $atts['range'] ) ) $atts['range'] = '';
        $result .= ' value="' . esc_attr($atts['value']) . '" name="' . $atts['name'] . '" data-format="' . $atts['format'] . '" data-step="' . $atts['step'] . '" data-range="' . $atts['range'] . '" data-duration="' . $atts['duration'] . '"';
        $result .= self::common_attributes( $atts, $tag, $settings );
        $result .= ' />';
        
        // @since 4.9.3 - Adaptive placeholders
        $result .= self::adaptivePlaceholders( $settings, $atts, $tag );

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }    
    public static function rating($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation
        
        // Get default value
        $atts['value'] = self::get_default_value($tag, $atts, $settings, $entry_data);   

        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        $result .= '<div class="super-rating">';

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $i=1;
        while( $i < 6 ) {
            $result .= '<i class="fas fa-star super-rating-star ' . ($i<=(int) $atts['value'] ? 'super-active ' : '') . $atts['class'] . '"></i>';
            $i++;
        }

        $result .= '<input class="super-shortcode-field super-star-rating" type="hidden"';
        $result .= ' value="' . esc_attr($atts['value']) . '" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag, $settings );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }

    public static function countries($x) {
        extract(self::extract($x));
        $tag = 'dropdown';
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        // Get default value
        $atts['value'] = self::get_default_value($tag, $atts, $settings, $entry_data);   

        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        $multiple = '';
        if( !isset( $atts['maxlength'] ) ) $atts['maxlength'] = 0;
        if( !isset( $atts['minlength'] ) ) $atts['minlength'] = 0;
        if( ($atts['minlength']>1) || ($atts['maxlength']>1) ) $multiple = ' multiple';

        $result .= '<input class="super-shortcode-field" type="hidden"';
        $result .= ' value="' . esc_attr($atts['value']) . '" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag, $settings );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        // @since 1.2.8     - auto scroll to value after key press
        if(empty($atts['disable_filter'])){
            $result .= '<input tabindex="-1" type="text" name="super-dropdown-search" value="" data-logic="' . esc_attr($atts['filter_logic']) . '" />';
        }

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<ul class="super-dropdown-list' . $multiple . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '">';
        if( !empty( $atts['placeholder'] ) ) {
            $result .= '<li data-value="" class="super-item super-placeholder">' . $atts['placeholder'] . '</li>';
        }else{
            $result .= '<li data-value="" class="super-item super-placeholder"></li>';
        }
        
        // @since 1.1.4 - use wp_remote_fopen instead of curl
        $countries = array();
        if ( file_exists( SUPER_PLUGIN_DIR . '/countries.txt' ) ) {
            $countries = wp_remote_fopen( SUPER_PLUGIN_FILE . 'countries.txt' );
            $countries = explode( "\n", $countries );
        }

        // @since 2.8.0 - give the possibility to filter countries list (currently used by register & login for woocommerce countries)
        $countries = apply_filters( 'super_countries_list_filter', $countries, array( 'name'=>$atts['name'], 'settings'=>$settings ) );

        foreach( $countries as $k => $v ){
            $v = trim($v);
            $result .= '<li class="super-item" data-value="' . esc_attr( is_string($k) ? esc_attr($k) : esc_attr($v) ) . '" data-search-value="' . esc_attr( $v ) . '">' . $v . '</li>'; 
        }
        $result .= '</ul>';
        $result .= '<span class="super-dropdown-arrow"><span class="super-after"><i class="fas fa-caret-down"></i></span></span>';
        // @since 4.9.3 - Adaptive placeholders
        $result .= self::adaptivePlaceholders( $settings, $atts, $tag );
        $result .= '</div>';

        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }
    public static function password($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        // Get default value
        $atts['value'] = self::get_default_value($tag, $atts, $settings, $entry_data);

        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<input tabindex="-1" class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="password"';
        $result .= ' value="' . esc_attr($atts['value']) . '" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag, $settings );
        $result .= ' />';

        // @since 4.9.3 - Adaptive placeholders
        $result .= self::adaptivePlaceholders( $settings, $atts, $tag );

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }
    public static function hidden($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        if( !isset( $atts['exclude'] ) ) $atts['exclude'] = 0;
        if( !isset( $atts['exclude_entry'] ) ) $atts['exclude_entry'] = '';

        // @since 2.8.0 - invoice numbers
        if( !isset( $atts['code_invoice'] ) ) $atts['code_invoice'] = '';
        if( !isset( $atts['code_invoice_padding'] ) ) $atts['code_invoice_padding'] = '';

        // @since 2.2.0 - random code generation
        if( !isset( $atts['enable_random_code'] ) ) $atts['enable_random_code'] = '';
        $codeSettings = array();
        if($atts['enable_random_code']=='true'){
            if( !isset( $atts['code_invoice_key'] ) ) $atts['code_invoice_key'] = '';
            if( !isset( $atts['code_length'] ) ) $atts['code_length'] = 7;
            if( !isset( $atts['code_characters'] ) ) $atts['code_characters'] = '1';
            if( !isset( $atts['code_prefix'] ) ) $atts['code_prefix'] = '';
            if( !isset( $atts['code_suffix'] ) ) $atts['code_suffix'] = '';
            if( !isset( $atts['code_uppercase'] ) ) $atts['code_uppercase'] = '';
            if( !isset( $atts['code_lowercase'] ) ) $atts['code_lowercase'] = '';
            $codeSettings = array(
                'invoice_key' => $atts['code_invoice_key'],
                'len' => $atts['code_length'],
                'char' => $atts['code_characters'],
                'pre' => $atts['code_prefix'],
                'inv' => $atts['code_invoice'],
                'invp' => $atts['code_invoice_padding'],
                'suf' => $atts['code_suffix'],
                'upper' => $atts['code_uppercase'],
                'lower' => $atts['code_lowercase']
            );
        }

        // Get default value
        $atts['value'] = self::get_default_value($tag, $atts, $settings, $entry_data);
        
        // When invoice_key is not empty, and either of prefix/suffix is empty display error message
        if($atts['enable_random_code']=='true'){
            $atts['value'] = SUPER_Common::generate_random_code($codeSettings, false);
            if($codeSettings['invoice_key']!==''){
                if($codeSettings['pre']==='' && $codeSettings['suf']===''){
                    return '<p style="color:red;"><strong>Error:</strong> when using a "Unique invoice key" you must either set a "Code prefix" or "Code suffix" to avoid possible number conflicts</p>';
                }
            }
        }

        $classes = ' hidden';
        $result = self::opening_tag( $tag, $atts, $classes );
        
        $result .= '<input class="super-shortcode-field" type="hidden"';
        if( !empty($atts['name']) ) $result .= ' name="' . $atts['name'] . '"';
        if( !empty($atts['originalFieldName']) ) {
            // Original Field Name (required/used by dynamic columns, to allow nested dynamic columns, javascript uses this data attribute)
            $result .= ' data-oname="' . explode('[', $atts['originalFieldName'])[0] . '"';
        }
        $result .= ' value="' . esc_attr($atts['value']) . '" data-default-value="' . esc_attr($atts['value']) . '" data-absolute-default="' . esc_attr($atts['value']) . '"';
        if( !empty($atts['email']) ) $result .= ' data-email="' . $atts['email'] . '"';
        if( !empty($atts['exclude']) ) $result .= ' data-exclude="' . $atts['exclude'] . '"';
        if( !empty($atts['exclude_entry']) ) $result .= ' data-exclude-entry="' . $atts['exclude_entry'] . '"';
        if( $atts['enable_random_code']=='true' ) $result .= ' data-code="' . $atts['enable_random_code'] . '"';
        if( $atts['code_invoice']=='true' ) $result .= ' data-invoice-padding="' . $atts['code_invoice_padding'] . '"';

        if(!empty($codeSettings)) $result .= ' data-codeSettings="' . esc_attr(json_encode($codeSettings)) . '"';
        $result .= ' />';

        $result .= self::loop_variable_conditions( $atts );
        $result .= '</div>';
        return $result;
    }

    public static function recaptcha($x){
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        
        $global_settings = SUPER_Common::get_global_settings();
        if(empty($atts['version'])) $atts['version'] = 'v2';
        $class = '';
        if($atts['version']==='v3'){
            $class = 'super-remove-margin';
            if( empty( $global_settings['form_recaptcha_v3'] ) ) $global_settings['form_recaptcha_v3'] = '';
            if( empty( $global_settings['form_recaptcha_v3_secret'] ) ) $global_settings['form_recaptcha_v3_secret'] = '';
            wp_enqueue_script('recaptcha', '//www.google.com/recaptcha/api.js?onload=SUPERreCaptcha&render=' . $global_settings['form_recaptcha_v3']);
        }else{
            wp_enqueue_script('recaptcha', '//www.google.com/recaptcha/api.js?onload=SUPERreCaptcha&render=explicit');
        }
        $result = self::opening_tag( $tag, $atts, $class );

        if( empty( $atts['error'] ) ) $atts['error'] = '';
        if( empty( $atts['align'] ) ) $atts['align'] = '';
        if( !empty( $atts['align'] ) ) $atts['align'] = ' align-' . $atts['align'];

        $api_missing_msg = sprintf( 
            esc_html__( 
                'Please enter your reCAPTCHA API keys in %sSuper Forms > Settings > Form Settings%s',
                'super-forms' 
            ),
            '<a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#form-settings') . '">', 
            '</a>' 
        );
        if($atts['version']==='v3'){
            $result .= '<div class="super-recaptcha g-recaptcha" data-sitekey="' . $global_settings['form_recaptcha_v3'] . '" data-size="invisible"></div>';
            if( ( $global_settings['form_recaptcha_v3']=='' ) || ( $global_settings['form_recaptcha_v3_secret']=='' ) ) {
                $result .= '<strong style="color:red;">' . $api_missing_msg . '</strong>';
            }
        }else{
            if( empty( $global_settings['form_recaptcha'] ) ) $global_settings['form_recaptcha'] = '';
            if( empty( $global_settings['form_recaptcha_secret'] ) ) $global_settings['form_recaptcha_secret'] = '';
            $result .= '<div class="super-recaptcha' . $atts['align'] . '" data-sitekey="' . $global_settings['form_recaptcha'] . '"></div>';
            if( ( $global_settings['form_recaptcha']=='' ) || ( $global_settings['form_recaptcha_secret']=='' ) ) {
                $result .= '<strong style="color:red;">' . $api_missing_msg . '</strong>';
            }
        }

        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }

    public static function image($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'html_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation
        
        $result = self::opening_tag( $tag, $atts, 'align-' . $atts['alignment'] );
        $style = '';
        if( $atts['height']!=0 ) $style .= 'max-height:' . $atts['height'] . 'px;';
        if( $atts['width']!=0 ) $style .= 'max-width:' . $atts['width'] . 'px;';
        $url = '';
        if( !isset( $atts['link'] ) ) $atts['link'] = '';
        if( $atts['link']!='' ) {
            if( $atts['link']=='custom' ) {
                $url = $atts['custom_link'];
            }else{
                $url = get_permalink( $atts['link'] );
            }
            $url = ' href="' . esc_url($url) . '"';
        }
        $result .= '<div class="super-image align-' . esc_attr($atts['alignment']) . '" itemscope="itemscope" itemtype="https://schema.org/ImageObject">';
            $result .= '<div class="super-image-inner">';
                if( !isset( $atts['target'] ) ) $atts['target'] = '';
                if(!empty($url)) $result .= '<a target="' . esc_attr($atts['target']) . '"' . $url . '>';
                    // @since 1.9 - custom class
                    if( !isset( $atts['class'] ) ) $atts['class'] = '';
                    if( !isset( $atts['image'] ) ) $atts['image'] = 0;
                    $attachment_id = absint($atts['image']);
                    if( $attachment_id===0 ) {
                        $url = SUPER_PLUGIN_FILE . 'assets/images/image-icon.png';
                        $result .= '<img src="' . esc_url($url) . '"' . ($atts['class']!='' ? ' class="' . esc_attr($atts['class']) . '"' : '') . ' itemprop="contentURL"';
                    }else{
                        $title = get_the_title( $attachment_id );
                        $url = wp_get_attachment_url( $attachment_id );
                        $alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                        $result .= '<img src="' . esc_url($url) . '"' . ($atts['class']!='' ? ' class="' . esc_attr($atts['class']) . '"' : '') . ' alt="' . esc_attr($alt) . '" title="' . esc_attr($title) . '" itemprop="contentURL"';
                    } 
                    if( !empty( $style ) ) $result .= ' style="' . esc_attr($style) . '"';
                    $result .= '>';
                if(!empty($url)) $result .= '</a>';
            $result .= '</div>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }

    // @since 1.2.5
    public static function heading($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'html_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        $result = self::opening_tag( $tag, $atts );
        if( !empty($atts['title']) ) {
            $result .= '<div class="super-heading-title';
            if(!empty($atts['heading_align']) && $atts['heading_align']!=='none') {
                $result .= ' super-align-'.$atts['heading_align']; 
            } 
            $result .= '"' . SUPER_Common::get_tags_attributes($atts['title']) . '>';

            $styles = '';
            if(!empty($atts['heading_size']) && $atts['heading_size']!=='-1') $styles .= 'font-size:'.$atts['heading_size'].'px;'; 
            if(!empty($atts['heading_color'])) $styles .= 'color:'.$atts['heading_color'].';'; 
            if(!empty($atts['heading_weight']) && $atts['heading_weight']!=='none') $styles .= 'font-weight:'.$atts['heading_weight'].';'; 
            if(!empty($atts['heading_margin'])) $styles .= 'margin:'.$atts['heading_margin'].';'; 
            if(!empty($atts['heading_line_height'])){
                if($atts['heading_line_height']!=='-1'){
                    if($atts['heading_line_height']==='0'){
                        $styles .= 'line-height:normal;';
                    }else{
                        $styles .= 'line-height:'.$atts['heading_line_height'].'px;';
                    }
                }
            }
            if(!empty($styles)) $styles = ' style="'.$styles.'"';
            $result .= '<'.$atts['size'] . ($atts['class']!='' ? ' class="' . $atts['class'] . '"' : '') . $styles . '>';
            $result .= stripslashes($atts['title']);
            $result .= '</'.$atts['size'].'>';
            $result .= '</div>';
        }
        if( !empty($atts['desc']) ) {
            $styles = '';
            if(!empty($atts['desc_size']) && $atts['desc_size']!=='-1') $styles .= 'font-size:'.$atts['desc_size'].'px;'; 
            if(!empty($atts['desc_color'])) $styles .= 'color:'.$atts['desc_color'].';'; 
            if(!empty($atts['desc_weight']) && $atts['desc_weight']!=='none') $styles .= 'font-weight:'.$atts['desc_weight'].';'; 
            if(!empty($atts['desc_margin'])) $styles .= 'margin:'.$atts['desc_margin'].';'; 
            if(!empty($atts['desc_line_height'])){
                if($atts['desc_line_height']!=='-1'){
                    if($atts['desc_line_height']==='0'){
                        $styles .= 'line-height:normal;';
                    }else{
                        $styles .= 'line-height:'.$atts['desc_line_height'].'px;';
                    }
                }
            }
            if(!empty($styles)) $styles = ' style="'.$styles.'"';
            $result .= '<div class="super-heading-description' . ($atts['class']!='' ? ' ' . $atts['class'] : '');
            if(!empty($atts['desc_align']) && $atts['desc_align']!=='none') {
                $result .= ' super-align-'.$atts['desc_align']; 
            } 
            $result .= '"' . SUPER_Common::get_tags_attributes($atts['desc']) . ' ' . $styles . '>';
            $result .= '<div>' . stripslashes($atts['desc']) . '</div>';
            $result .= '</div>';
        }
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }

    public static function html($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'html_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

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
            $re = '/foreach\(([-_a-zA-Z0-9]{1,})|([-_a-zA-Z0-9]{1,})\[.*?(\):)|(?:<%|{)([-_a-zA-Z0-9]{1,})(?:}|%>)|(?:<%|{)([-_a-zA-Z0-9]{1,});.*?(?:}|%>)|(?:<%|{)([-_a-zA-Z0-9]{1,})\[.*?(?:}|%>)/';
            $str = $atts['html'];
            preg_match_all($re, $atts['html'], $matches, PREG_SET_ORDER, 0);
            $data_fields = array();
            foreach($matches as $k => $v){
                $length = count($v);
                if(empty($v[$length-1])) continue;
                $fieldName = $v[$length-1];
                $data_fields[$fieldName] = $fieldName;
            }
            $field_names = implode('}{', $data_fields);
            $html = $atts['html'];
            if( (!is_admin()) || ( (isset($_POST['action'])) && ($_POST['action']=='super_listings_edit_entry' || $_POST['action']=='super_language_switcher') ) ) {
                $html_code = '';
                if( !empty($atts['nl2br']) ) $html = nl2br($html);
                if(empty($field_names)){
                    $html_code = do_shortcode(stripslashes($html));
                }
            }else{
                if( !empty($_POST['action']) && ($_POST['action']==='super_load_preview' || $_POST['action']==='elementor_ajax') && is_admin()){
                    if( !empty($atts['nl2br']) ) $html = nl2br($html);
                    $html_code = stripslashes($html);
                }else{
                    $html_code = '<pre>'.htmlspecialchars(stripslashes($html)).'</pre>';
                }
            }

            $dataFields = '';
            if(!empty($field_names)) $dataFields = ' data-fields="{' . $field_names . '}"';
            $result .= '<div class="super-html-content' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"' . ($dataFields ? $dataFields : '') . '>' . $html_code . '</div>';
            $result .= '<textarea>' . do_shortcode( stripslashes($html) ) . '</textarea>';
            if(!empty($atts['name'])) {
                $result .= '<textarea class="super-shortcode-field super-hidden"';
                $result .= ' name="' . $atts['name'] . '"';
                $result .= self::common_attributes( $atts, $tag, $settings );
                $result .= ' >' . do_shortcode( stripslashes($html) ) . '</textarea>';
            }
        }
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }

    public static function divider($x) {
        extract(self::extract($x));
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
            $result .= '<span class="super-back-to-top"' . $i_styles . '><i class="fas fa-chevron-up"></i></span>';
        }
        $result .= '</div>';
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }
    public static function spacer($x){
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'html_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        $styles = '';
        if( $atts['height']!='' ) {
            $styles = 'height:' . $atts['height'] . 'px;';
        }
        $result = self::opening_tag( $tag, $atts, '', $styles );
        $result .= self::loop_conditions( $atts, $tag );
        $result .= '</div>';
        return $result;
    }
    public static function pdf_page_break($x){
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'html_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $class = '';
        if(!empty($atts['orientation'])){
            $class = 'pdf-orientation-'.$atts['orientation'];
        }
        $result = self::opening_tag( $tag, $atts, $class );
        $result .= '</div>';
        return $result;
    }


    /** 
     *  Google Map with API options
     *
     *  @since      3.5.0
    */
    public static function google_map($x){
        // In order to print google map load the libraries:
        //wp_enqueue_script( 'super-html-canvas', SUPER_PLUGIN_FILE.'lib/super-html-canvas.min.js', array(), SUPER_VERSION, false );   
        //wp_enqueue_script( 'super-pdf-gen', SUPER_PLUGIN_FILE.'lib/super-pdf-gen.min.js', array( 'super-html-canvas' ), SUPER_VERSION, false );          
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'html_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );

        $map_styles = 'min-height:' . $atts['min_height'] . 'px;';
        if(empty($atts['api_key'])) $atts['api_key'] = '';
        $url = '//maps.googleapis.com/maps/api/js?';
        if( !empty( $atts['api_region'] ) ){
            $url .= 'region='.$atts['api_region'].'&';
        }
        if( !empty( $atts['api_language'] ) ){
            $url .= 'language='.$atts['api_language'].'&';
        }
        $url .= 'key=' . $atts['api_key'] . '&libraries=drawing,geometry,places,visualization&callback=SUPER.google_maps_init';
        wp_enqueue_script( 'google-maps-api', $url, array( 'super-common' ), SUPER_VERSION, false );

        // Add field attributes if {tags} are being used
        $value = $atts['address'];
        // Directions API (route)
        $value .= $atts['origin'];
        $value .= $atts['destination'];
        $value .= $atts['directionsPanel'];
        $value .= $atts['travelMode'];
        $value .= $atts['unitSystem'];
        // Waypoints
        $value .= $atts['waypoints'];
        $value .= $atts['optimizeWaypoints'];
        $value .= $atts['provideRouteAlternatives'];
        $value .= $atts['avoidFerries'];
        $value .= $atts['avoidHighways'];
        $value .= $atts['avoidTolls'];
        $value .= $atts['region'];
        // UI settings
        $value .= $atts['zoom'];
        $value .= $atts['disableDefaultUI'];
        // we will implement this in a later version   // drivingOptions (only when travelMode is DRIVING)
        // we will implement this in a later version   $atts['departureTime']);
        // we will implement this in a later version   $atts['trafficModel']);
        // we will implement this in a later version   // transitOptions (only when travelMode is TRANSIT)
        // we will implement this in a later version   $atts['arrivalTime']);
        // we will implement this in a later version   $atts['transitDepartureTime']);
        // we will implement this in a later version   $atts['transitModes']);
        // we will implement this in a later version   $atts['routingPreference']);
        $names = SUPER_Common::get_data_fields_attribute(array('value'=>$value));

        // Polylines
        if( !empty($atts['enable_polyline']) ) {
            $polylines = explode("\n", $atts['polylines']);
            foreach( $polylines as $k => $v ) {
                $coordinates = explode("|", $v);
                if( count($coordinates)<2 ) {
                    $error = esc_html__( 'Incorrect latitude and longitude coordinates for Polylines, please correct and update element!', 'super-forms' );
                }else{
                    $lat = $coordinates[0];
                    $lng = $coordinates[1];
                    $names = SUPER_Common::get_data_fields_attribute(array('value'=>$lat.$lng));
                }
            }
        }
        $result = '<div class="super-google-map"' . (!empty($names) ? ' data-fields="{' . implode('}{', $names) . '}"' : '') . '>';
        if( (is_admin()) && (!empty($error)) ) {
            $result .= '<p><strong style="color:red;">' . $error . '</strong></p>';
        }
        $map_id = 'super-google-map-' . self::$current_form_id;
        $result .= '<div class="' . $map_id . '" id="' . $map_id . '" style="' . $map_styles . '">';
        if( empty($atts['api_key']) ) {
            $result .= '<strong style="color:red;">' . esc_html__( 'Please enter your "Google API key" and make sure you enabled the "Google Maps JavaScript API" library in order to generate a map', 'super-forms' ) . '</strong>';
        }
        $result .= '</div>';
        $result .= '<textarea disabled class="super-hidden">' . json_encode( $atts ) . '</textarea>';
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
    public static function button($x) {
        extract(self::extract($x));
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

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
        if( !isset( $GLOBALS['super_submit_button_found'] ) && $action==='submit' ) {
            $GLOBALS['super_submit_button_found'] = true;
        }

        if( !empty( $atts['name'] ) ) $name = $atts['name'];
        $name = stripslashes($name);

        if( !empty( $atts['loading'] ) ) $loading = $atts['loading'];
        
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
        
        $class = 'super-extra-shortcode super-shortcode super-field super-button super-clear-none';
        if($action==='prev' || $action==='next'){
            if($action==='prev'){
                $class .= ' super-prev-multipart';
            }else{
                $class .= ' super-next-multipart';
            }
        }else{
            $class .= ' super-form-button';
        }
        $class .= ' super-radius-' . $radius . ' super-type-' . $type . ' super-button-' . $size . ' super-button-align-' . $align . ' super-button-width-' . $width;
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
            $attributes .= ' data-color="' . esc_attr($color) . '"';
            $attributes .= ' data-light="' . esc_attr($light) . '"';
            $attributes .= ' data-dark="' . esc_attr($dark) . '"';
        }
        if( $color_hover!='' ) {
            $hover_light = SUPER_Common::adjust_brightness( $color_hover, 20 );
            $hover_dark = SUPER_Common::adjust_brightness( $color_hover, -30 );
            $attributes .= ' data-hover-color="' . esc_attr($color_hover) . '"';
            $attributes .= ' data-hover-light="' . esc_attr($hover_light) . '"';
            $attributes .= ' data-hover-dark="' . esc_attr($hover_dark) . '"';
        }
        if( $font!='' ) $attributes .= ' data-font="' . esc_attr($font) .'"';
        if( $font_hover!='' ) $attributes .= ' data-font-hover="' . esc_attr($font_hover) . '"';
        $result = '';

        $result .= '<div' . $attributes . ' data-radius="' . esc_attr($radius) . '" data-type="' . esc_attr($type) . '" class="' . esc_attr($class) . '">';
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
                if( !empty( $atts['target'] ) ) $atts['target'] = 'data-target="' . esc_attr($atts['target']) . '" ';
            }

            $result .= '<div ' . $atts['target'] . 'data-href="' . esc_attr($url) . '" class="super-button-wrap no_link' . ($atts['class']!='' ? ' ' . esc_attr($atts['class']) : '') . '">';
                if( ( $icon!='' ) && ( $icon_option!='none' ) ) {
                    $icon_tag = explode(' ', $icon);
                    if(isset($icon_tag[1])){
                        $icon_type = $icon_tag[0];
                        $icon_tag = str_replace('fa-', '', $icon_tag[1]);
                    }else{
                        $default = explode(';', $icon);
                        $icon_tag = $default[0];
                        $icon_type = 'fas';
                        if(isset($default[1])){
                            $icon_type = $default[1]; // use the existing type
                        }
                    }
                    $result .= '<span class="super-before"><i class="' . $icon_type . ' fa-'.SUPER_Common::fontawesome_bwc($icon_tag).'"></i></span>';
                }
                $result .= '<div class="super-button-name" data-action="' . esc_attr($action) . '" data-normal="' . esc_attr($name) . '" data-loading="' . esc_attr($loading) . '">';
                    $result .= $name;
                    // @since 3.9.0 - option for print action to use custom HTML
                    if( !empty($atts['print_custom']) ) {
                        if( !empty($atts['print_file']) ) {
                            $result .= '<input type="hidden" name="print_file" value="' . absint($atts['print_file']) . '" />';
                        }
                    }
                $result .= '</div>';
                $result .= '<span class="super-after"></span>';
            $result .= '</div>';
        $result .= '</div>';
        return $result;
    }


    /** 
     *  Output the shortcode element on front-end
     *
     * @param  int  $dynamicLevel
     * @param  string  $tag
     * @param  string  $group
     * @param  array   $data
     * @param  array   $inner
     * @param  array   $shortcodes
     * @param  array   $settings
     * @param  array   $i18n
     * @param  boolean $builder
     * @param  array   $entry_data
     * @param  int     $dynamic
     * @param  array   $dynamic_field_names
     * @param  array   $inner_field_names
     *
     *  @since      1.0.0
    */
    public static function output_element_html($x){
        extract(self::extract($x));
        // @IMPORTANT: before we proceed we must make sure that the "Default value" of a field will still be available
        // Otherwise when a user would duplicate a column that was populated with Entry data this "Default value" would be replaced with the Entry value
        // This is not what we want, because when duplicating a column we would like to reset each element to it's original state (Default value)
        // The below `absolute_default` will be retrieved on the elements attribute called `data-absolute-default=""`
        // This value is then used when a column is duplicated so we can reset each field to it's initial default value
        if(!empty($data['name'])){
            $data['originalFieldName'] = $data['name'];
            $element = array('tag' => $tag, 'data' => $data, 'group' => $group);
            $data['absolute_default'] = SUPER_Common::get_absolute_default_value($element, $shortcodes);
        }

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
        if( !isset( $shortcodes[$group]['shortcodes'][$tag]['callback'] ) ) {
            return '';
        }
        $callback = $shortcodes[$group]['shortcodes'][$tag]['callback'];
        $callback = explode( '::', $callback );
        $class = $callback[0];
        $function = $callback[1];
        $data = json_decode(json_encode($data), true);
        $inner = json_decode(json_encode($inner), true);
        if($settings['theme_hide_icons']==='yes'){
            unset($data['icon']);
            unset($data['icon_align']);
            unset($data['icon_position']);
        }

        return call_user_func( array( $class, $function ), array('tag'=>$tag, 'atts'=>$data, 'inner'=>$inner, 'shortcodes'=>$shortcodes, 'settings'=>$settings, 'i18n'=>$i18n, 'builder'=>$builder, 'entry_data'=>$entry_data, 'dynamic'=>$dynamic, 'dynamic_field_names'=>$dynamic_field_names, 'inner_field_names'=>$inner_field_names, 'formProgress'=>$formProgress) );
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
                // Fontawesome 5+ compatibility
                if(isset(explode(';', $value['icon'])[1])){
                    $return .= '<i class="'.explode(';', $value['icon'])[1].' fa-'.explode(';', $value['icon'])[0].'"></i>'.$value['name'];
                }else{
                    $return .= '<i class="fas fa-'.$value['icon'].'"></i>'.$value['name'];
                }
                $return .= '<div class="content" style="display:none;">';
                if( isset( $value['content'] ) ) $return .= $value['content'];
                $return .= '</div>';
            }
            if( isset( $value['predefined'] ) ) {
                $pre = htmlentities( json_encode( $value['predefined'] ), ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED );
                $return .= '<textarea class="predefined" style="display:none;">' . $pre . '</textarea>';
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
            'name' => esc_html__( 'Unique field name', 'super-forms' ) . ' *', 
            'desc' => esc_html__( 'Must be an unique name (required)', 'super-forms' ),
            'default' => ( !isset( $attributes['name'] ) ? $default : $attributes['name'] ),
            'required' => true,
            'filter' => true
        );
        return $array;
    }
    public static function email( $attributes=null, $default='' ) {
        $array = array(
            'name' => esc_html__( 'E-mail & Contact Entry Label', 'super-forms' ), 
            'label' => esc_html__( 'When left blank it defaults to the field name defined above. Inside dynamic columns, you can use %d to determine where the counter should be placed e.g: "Product %d quantity:" would be converted into "Product 3 quantity:"', 'super-forms' ),
            'desc' => esc_html__( 'Indicates the field in emails and contact entries. (required)', 'super-forms' ),
            'default' => ( !isset( $attributes['email'] ) ? $default : $attributes['email'] ),
            'required' => false,
            'i18n' => true
        );
        return $array;
    }
    public static function label( $attributes=null, $default='' ) {
        $array = array(
            'name' => esc_html__( 'Field Label', 'super-forms' ), 
            'desc' => esc_html__( 'Will be visible in front of your field.', 'super-forms' ).' ('.esc_html__( 'leave blank to remove', 'super-forms' ).')',
            'default' => ( !isset( $attributes['label'] ) ? $default : $attributes['label'] ),
            'i18n' => true
        );
        return $array;
    }    
    public static function description( $attributes=null, $default='') {
        $array = array(
            'name' => esc_html__( 'Field description', 'super-forms' ), 
            'desc' => esc_html__( 'Will be visible in front of your field.', 'super-forms' ).' ('.esc_html__( 'leave blank to remove', 'super-forms' ).')',
            'default' => ( !isset( $attributes['description'] ) ? $default : $attributes['description'] ),
            'i18n' => true
        );
        return $array;
    }
    public static function icon( $attributes=null, $default='user' ) {
        $icon = array(
            'default' => ( !isset( $attributes['icon'] ) ? $default : $attributes['icon'] ),
            'name' => esc_html__( 'Select an Icon', 'super-forms' ), 
            'type' => 'icon',
            'desc' => esc_html__( 'Leave blank if you prefer to not use an icon.', 'super-forms' )
        );
        return $icon;
    }
    public static function placeholder( $attributes=null, $default=null ) {
        $array = array(
            'default' => ( !isset( $attributes['placeholder'] ) ? $default : $attributes['placeholder'] ),
            'name' => esc_html__( 'Placeholder', 'super-forms' ), 
            'desc' => esc_html__( 'Indicate what the user needs to enter or select. (leave blank to remove)', 'super-forms' ),
            'i18n' => true
        );
        return $array;
    }
    public static function placeholderFilled( $attributes=null, $default=null ) {
        $array = array(
            'default' => ( !isset( $attributes['placeholder'] ) ? $default : $attributes['placeholder'] ),
            'name' => esc_html__( 'Placeholder when the field is filled out', 'super-forms' ), 
            'label' => '(' . esc_html__( 'only used when Adaptive Placeholders are enabled', 'super-forms' ) . ')',
            'i18n' => true
        );
        return $array;
    }
    public static function width( $attributes=null, $default=0, $min=0, $max=600, $steps=10, $name=null, $desc=null ) {
        if( empty( $name ) ) $name = esc_html__( 'Field width in pixels', 'super-forms' );
        if( empty( $desc ) ) $desc = esc_html__( 'Set to 0 to use default CSS width.', 'super-forms' );
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
        if( empty( $name ) ) $name = esc_html__( 'Field width in pixels', 'super-forms' );
        if( empty( $desc ) ) $desc = esc_html__( 'Set to 0 to use default CSS width.', 'super-forms' );
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
        if( empty($name ) ) $name = esc_html__( 'Min characters/selections allowed', 'super-forms' );
        if( empty($desc ) ) $desc = esc_html__( 'Set to 0 to remove limitations.', 'super-forms' );
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
        if( empty( $name ) ) $name = esc_html__( 'Max characters/selections allowed', 'super-forms' );
        if( empty( $desc ) ) $desc = esc_html__( 'Set to 0 to remove limitations.', 'super-forms' );
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
    public static function sf_retrieve_method($value, $parent){
        $array = array();
        $values = array();
        $array['default'] = ( !isset( $value ) ? 'custom' : $value );
        if($parent==='enable_keywords'){
            $values['free'] = esc_html__( 'Allow everything (no limitations)', 'super-forms' );
            $array['parent'] = $parent;
            $array['filter_value'] = 'true';
            $array['default'] = ( !isset( $value ) ? 'free' : $value );
        }
        if($parent==='enable_auto_suggest'){
            $array['parent'] = $parent;
            $array['filter_value'] = 'true';
        }
        $values['custom'] = esc_html__( 'Custom items', 'super-forms' ); 
        $values['taxonomy'] = esc_html__( 'Specific taxonomy (categories)', 'super-forms' );
        $values['post_type'] = esc_html__( 'Specific posts (post_type)', 'super-forms' );
        $values['product_attribute'] = esc_html__( 'Product attribute (product_attributes)', 'super-forms' );
        $values['tags'] = esc_html__( 'Tags (post_tag)', 'super-forms' );
        $values['users'] = esc_html__( 'Users (wp_users)', 'super-forms' );
        $values['csv'] = esc_html__( 'CSV file', 'super-forms' );
        $values['author'] = esc_html__( 'Current Author meta data', 'super-forms' ); // @since 4.0.0 - retrieve current author data
        $values['post_meta'] = esc_html__( 'Current Page or Post meta data', 'super-forms' ); // @since 4.0.0 - retrieve current author data
        $values['post_terms'] = esc_html__( 'Current Page or Post terms (based on specified taxonomy slug)', 'super-forms' ); // @since 4.0.0 - retrieve current author data
        $values['db_table'] = esc_html__( 'Specific database table', 'super-forms' ); // @since 4.4.1 - retrieve from a custom database table
        $array['name'] = esc_html__( 'Retrieve method', 'super-forms' );
        $array['desc'] = esc_html__( 'Select a method for retrieving items', 'super-forms' );
        $array['type'] = 'select';
        $array['filter'] = true;
        $array['values'] = $values;
        return $array;
    }

    public static function sf_retrieve_method_exclude_users($value, $parent){
        return array(
            'name' => esc_html__( 'Exclude user(s)', 'super-forms' ), 
            'label' => esc_html__( 'Enter the user ID\'s to exclude seperated by comma\'s', 'super-forms' ), 
            'default'=> ( !isset( $value ) ? '' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'users'
        );
    }
    public static function sf_retrieve_method_role_filters($value, $parent){
        return array(
            'type' => 'textarea',
            'name' => esc_html__( 'Filter users by role(s)', 'super-forms' ),
            'label' => esc_html__( 'Define each role on a new line. For instance, if you want to return only WooCommerce customers, you can use: customer', 'super-forms' ),
            'default'=> ( !isset( $value ) ? '' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'users'
        );
    }
    public static function sf_retrieve_method_user_label($value, $parent){
        return array(
            'name' => esc_html__( 'Label deffinition for each user', 'super-forms' ),
            'label' => esc_html__( 'Define here how you want to list your users e.g: [#{ID} - {first_name} {last_name} ({user_email})] would translate to: [#1845 - John Wilson (john@email)]', 'super-forms' ),
            'placeholder' => "#{ID} - {first_name} {last_name} ({user_email})",
            'default'=> ( !isset( $value ) ? '' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'users'
        );
    }
    public static function sf_retrieve_method_user_meta_keys($value, $parent){
        return array(
            'type' => 'textarea',
            'name' => esc_html__( 'Define user data or user meta data to return as value', 'super-forms' ), 
            'label' => esc_html__( "Put each key on a new line, for instance if you want to return the user billing address you could enter:\nID\nbilling_first_name\nbilling_last_name\nbilling_company\nbilling_email\nbilling_phone\nbilling_address_1\nbilling_city\nbilling_state\nbilling_postcode\nbilling_country\n\nWhen retrieving the value in the form dynamically you can use tags like so: {fieldname;1} (to retrieve the user ID) and {fieldname;2} (to retrieve the city) and so on...", 'super-forms' ),
            'placeholder' => "ID\nbilling_first_name\nbilling_last_name\nbilling_company\nbilling_email\nbilling_phone\nbilling_address_1\nbilling_city\nbilling_state\nbilling_postcode\nbilling_country",
            'default'=> ( !isset( $value ) ? '' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'users'
        );
    }
    public static function sf_retrieve_method_db_table($value, $parent){
        return array(
            'required' => true,
            'name' => esc_html__( 'Database table name', 'super-forms' ), 
            'label' => esc_html__( 'Enter the table name including the prefix e.g: wp_mycustomtable', 'super-forms' ), 
            'default'=> ( !isset( $value ) ? '' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'db_table'
        );
    }
    public static function sf_retrieve_method_db_row_value($value, $parent){
        return array(
            'name' => esc_html__( 'Use {tags} to define the returned Value per row', 'super-forms' ),
            'label' => esc_html__( 'Example to return the row ID: <strong>{ID}</strong>', 'super-forms' ),
            'desc' => esc_html__( 'Any table column can be returned by using {tags} as long as the columns name exists', 'super-forms' ),
            'default'=> ( !isset( $value ) ? '' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'db_table'
        );
    }
    public static function sf_retrieve_method_db_row_label($value, $parent){
        return array(
            'name' => esc_html__( 'Use {tags} to define the returned Label per row', 'super-forms' ),
            'label' => esc_html__( 'Example, to return the row First name: <strong>{first_name}</strong>', 'super-forms' ),
            'desc' => esc_html__( 'Any table column can be returned by using {tags} as long as the columns name exists', 'super-forms' ),
            'default'=> ( !isset( $value ) ? '' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'db_table'
        );
    }
    public static function sf_retrieve_method_post_terms_label($value, $parent){
        return array(
            'required' => true,
            'name' => esc_html__( 'Choose terms label to return', 'super-forms' ), 
            'type' => 'select',
            'values' => array(
                'names' => esc_html__( 'Names (default)', 'super-forms' ),
                'slugs' => esc_html__( 'Slugs', 'super-forms' ),
                'ids' => esc_html__( 'ID\'s', 'super-forms' ),
            ),
            'default'=> ( !isset( $value ) ? 'names' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'post_terms'
        );
    }
    public static function sf_retrieve_method_post_terms_value($value, $parent){
        return array(
            'required' => true,
            'name' => esc_html__( 'Choose terms value to return', 'super-forms' ), 
            'type' => 'select',
            'values' => array(
                'names' => esc_html__( 'Names', 'super-forms' ),
                'slugs' => esc_html__( 'Slugs (default)', 'super-forms' ),
                'ids' => esc_html__( 'ID\'s', 'super-forms' ),
            ),
            'default'=> ( !isset( $value ) ? 'slugs' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'post_terms'
        );
    }
    public static function sf_retrieve_method_author_field($value, $parent){
        return array(
            'required' => true,
            'name' => esc_html__( 'Choose meta field name', 'super-forms' ), 
            'label' => sprintf( esc_html__( 'You would normally be using a textarea field where each option is put on a new line. You can also seperate label and value with pipes. Example textarea value would be:%1$sOption 1|option_1%1$sOption 2|option_2%1$setc...%1$s(ACF fields are also supported)', 'super-forms' ), '<br />' ), 
            'default'=> ( !isset( $value ) ? '' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'author,post_meta'
        );
    }
    public static function sf_retrieve_method_author_option_explode($value, $parent){
        return array(
            'name' => esc_html__( 'Choose label value break method', 'super-forms' ), 
            'label' => esc_html__( 'This will split up the label and value of each option. By default the label and value will be split by a pipe "|" character.' ), 
            'default'=> ( !isset( $value ) ? '|' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'author,post_meta'
        );
    }
    public static function sf_retrieve_method_author_line_explode($value, $parent){
        return array(
            'name' => esc_html__( 'Choose line break method (optional)', 'super-forms' ), 
            'label' => esc_html__( 'By default each value that is placed on a new line will be converted to an option to choose from. In case you have a text field with comma seperated values, you can change this to be a comma instead.' ), 
            'default'=> ( !isset( $value ) ? '' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'author,post_meta'
        );
    }
    public static function sf_retrieve_method_custom_items($value, $parent, $type){
        return array(
            'type' => $type,
            'default' => ( !isset( $value ) ? 
                array(
                    array(
                        'checked' => false,
                        'label' => esc_html__( 'First choice', 'super-forms' ),
                        'value' => esc_html__( 'first_choice', 'super-forms' )
                    ),
                    array(
                        'checked' => false,
                        'label' => esc_html__( 'Second choice', 'super-forms' ),
                        'value' => esc_html__( 'second_choice', 'super-forms' )
                    ),
                    array(
                        'checked' => false,
                        'label' => esc_html__( 'Third choice', 'super-forms' ),
                        'value' => esc_html__( 'third_choice', 'super-forms' )
                    )
                ) : $value
            ),
            'filter' => true,
            'parent' => $parent,
            'filter_value' => 'custom',
            'i18n' => true
        );
    }
    public static function sf_retrieve_method_csv($value, $parent){
        return array(
            'name' => esc_html__( 'Upload CSV file', 'super-forms' ), 
            'default'=> ( !isset( $value ) ? '' : $value ),
            'type' => 'file',
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'csv',
            'file_type'=>'text/csv',
            'i18n' => true
        );
    }
    public static function sf_retrieve_method_delimiter($value, $parent){
        return array(
            'name' => esc_html__( 'Custom delimiter', 'super-forms' ), 
            'label' => esc_html__( 'Set a custom delimiter to seperate the values on each row', 'super-forms' ), 
            'default'=> ( !isset( $value ) ? ',' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'csv',
            'i18n' => true
        );
    }
    public static function sf_retrieve_method_enclosure($value, $parent){
        return array(
            'name' => esc_html__( 'Custom enclosure', 'super-forms' ), 
            'label' => esc_html__( 'Set a custom enclosure character for values', 'super-forms' ), 
            'default'=> ( !isset( $value ) ? '"' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'csv',
            'i18n' => true
        );
    }
    public static function sf_retrieve_method_taxonomy($value, $parent){
        return array(
            'name' => esc_html__( 'Taxonomy slug', 'super-forms' ), 
            'label' => esc_html__( 'Enter the taxonomy slug name e.g category or product_cat', 'super-forms' ), 
            'default'=> ( !isset( $value ) ? 'category' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'taxonomy,post_terms'
        );
    }
    public static function sf_retrieve_method_product_attribute($value, $parent){
        return array(
            'name' => esc_html__( 'Product attribute slug', 'super-forms' ), 
            'label' => esc_html__( 'Enter the attribute slug name e.g color or condition', 'super-forms' ), 
            'default'=> ( !isset( $value ) ? '' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'product_attribute'
        );
    }                       
    public static function sf_retrieve_method_post($value, $parent){
        return array(
            'name' => esc_html__( 'Post type (e.g page, post or product)', 'super-forms' ), 
            'label' => esc_html__( 'Enter the name of the post type', 'super-forms' ),
            'default'=> ( !isset( $value ) ? 'post' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'post_type'
        );
    }
    public static function sf_retrieve_method_post_status($value, $parent){
        return array(
            'name' => esc_html__( 'Post status (e.g any, publish, inherit, pending, private, future, draft, trash)', 'super-forms' ), 
            'label' => esc_html__( 'Seperated each post status by a comma, enter "any" for all post statuses', 'super-forms' ),
            'default'=> ( !isset( $value ) ? 'publish' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'post_type'
        );
    }
    public static function sf_retrieve_method_post_limit($value, $parent){
        return array(
            'name' => esc_html__( 'Limit (set to -1 for no limit)', 'super-forms' ),
            'label' => esc_html__( 'How many posts should be retrieved at a maximum?', 'super-forms' ),
            'default' => ( !isset( $value ) ? 30 : $value ),
            'type' => 'slider', 
            'min' => -1, 
            'max' => 100, 
            'steps' => 1, 
            'filter' => true,
            'parent' => $parent, // display
            'filter_value' => 'post_type'
        );
    }
    public static function sf_display($value){
        return array(
            'name' => esc_html__( 'Display Layout', 'super-forms' ),
            'label' => esc_html__( 'Select how the items should be displayed', 'super-forms' ),
            'default' => ( !isset( $value ) ? 'vertical' : $value ),
            'type' => 'select', 
            'values' => array(
                'vertical' => esc_html__( 'List (vertical)', 'super-forms' ), 
                'horizontal' => esc_html__( 'List (horizontal)', 'super-forms' ), 
                'grid' => esc_html__( 'Grid', 'super-forms' ), 
                'slider' => esc_html__( 'Slider (Carousel)', 'super-forms' ), 
            ),
            'filter' => true
        );
    }
    public static function sf_display_columns($value, $parent){
        return array(
            'name' => esc_html__( 'Number of columns (1 up to 10)', 'super-forms' ),
            'label' => esc_html__( 'Choose how many columns your Grid or Slider will display.', 'super-forms' ),
            'default' => ( !isset( $value ) ? 4 : $value ),
            'type' => 'slider', 
            'min' => 1, 
            'max' => 10, 
            'steps' => 1, 
            'filter' => true,
            'parent' => $parent, // display
            'filter_value' => 'grid,slider'
        );
    }
    public static function sf_display_minwidth($value, $parent){
        return array(
            'name' => esc_html__( 'Minimum width of each item', 'super-forms' ),
            'label' => esc_html__( 'Choose a minimum width that each item must have', 'super-forms' ),
            'default' => ( !isset( $value ) ? 100 : $value ),
            'type' => 'slider', 
            'min' => 10, 
            'max' => 1000, 
            'steps' => 10, 
            'filter' => true,
            'parent' => $parent, // display
            'filter_value' => 'slider'
        );
    }
    public static function sf_display_nav($value, $parent){
        return array(
            'allow_empty' => true,
            'default' => ( !isset( $value ) ? 'true' : $value ),
            'type' => 'checkbox', 
            'values' => array(
                'true' => esc_html__( 'Display prev/next buttons', 'super-forms' ),
            ),
            'filter' => true,
            'parent' => $parent, // display
            'filter_value' => 'slider'
        );
    }
    public static function sf_display_dots_nav($value, $parent){
        return array(
            'allow_empty' => true,
            'default' => ( !isset( $value ) ? 'true' : $value ),
            'type' => 'checkbox', 
            'values' => array(
                'true' => esc_html__( 'Display dots navigation', 'super-forms' ),
            ),
            'filter' => true,
            'parent' => $parent, // display
            'filter_value' => 'slider'
        );
    }
    public static function sf_display_rows($value, $parent){
        return array(
            'name' => esc_html__( 'Maximum number of rows to display', 'super-forms' ),
            'label' => esc_html__( 'Choose how many rows your Grid will display. (0 = no limit)', 'super-forms' ),
            'default' => ( !isset( $value ) ? 3 : $value ),
            'type' => 'slider', 
            'min' => 0, 
            'max' => 10, 
            'steps' => 1, 
            'filter' => true,
            'parent' => $parent, // display
            'filter_value' => 'grid'
        );
    }
    public static function sf_display_featured_image($value, $parent){
        return array(
            'allow_empty' => true,
            'default' => ( !isset( $value ) ? 'true' : $value ),
            'type' => 'checkbox', 
            'values' => array(
                'true' => esc_html__( 'Show featured image', 'super-forms' ),
            ),
            'filter' => true,
            'parent' => $parent, // retrieve_method
            'filter_value' => 'post_type'
        );
    }
    public static function sf_display_title($value, $parent){
        return array(
            'allow_empty' => true,
            'default' => ( !isset( $value ) ? 'true' : $value ),
            'type' => 'checkbox', 
            'values' => array(
                'true' => esc_html__( 'Show post title', 'super-forms' ),
            ),
            'filter' => true,
            'parent' => $parent, // retrieve_method
            'filter_value' => 'post_type'
        );
    }
    public static function sf_display_excerpt($value, $parent){
        return array(
            'allow_empty' => true,
            'default' => ( !isset( $value ) ? 'true' : $value ),
            'type' => 'checkbox', 
            'values' => array(
                'true' => esc_html__( 'Show post excerpt', 'super-forms' ),
            ),
            'filter' => true,
            'parent' => $parent, // retrieve_method
            'filter_value' => 'post_type'
        );
    }
    public static function sf_display_price($value, $parent){
        return array(
            'allow_empty' => true,
            'default' => ( !isset( $value ) ? 'true' : $value ),
            'type' => 'checkbox', 
            'values' => array(
                'true' => esc_html__( 'Show product price', 'super-forms' ),
            ),
            'filter' => true,
            'parent' => $parent, // retrieve_method_post
            'filter_value' => 'product'
        );
    }
    public static function sf_retrieve_method_orderby($value, $parent){
        return array(
            'name' => esc_html__( 'Order By', 'super-forms' ), 
            'label' => esc_html__( 'Select how you want to order the items', 'super-forms' ), 
            'default'=> ( !isset( $value ) ? 'title' : $value ),
            'type' => 'select', 
            'values' => array(
                'title' => esc_html__( 'Order by title (default)', 'super-forms' ), 
                'date' => esc_html__( 'Order by date', 'super-forms' ), 
                'ID' => esc_html__( 'Order by post id', 'super-forms' ), 
                'author' => esc_html__( 'Order by author', 'super-forms' ), 
                'modified' => esc_html__( 'Order by last modified date', 'super-forms' ), 
                'parent' => esc_html__( 'Order by post/page parent id', 'super-forms' ),
                'menu_order' => esc_html__( 'Order by menu order', 'super-forms' ),
                'price' => esc_html__( 'Order by Product price (WooCommerce only)', 'super-forms' )
            ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'post_type'
        );
    }
    public static function sf_retrieve_method_order($value, $parent){
        return array(
            'name' => esc_html__( 'Order', 'super-forms' ), 
            'label' => esc_html__( 'Select if you want to use Ascending or Descending order', 'super-forms' ), 
            'default'=> ( !isset( $value ) ? 'ASC' : $value ),
            'type' => 'select', 
            'values' => array(
                'ASC' => esc_html__( 'Ascending Order (default)', 'super-forms' ), 
                'DESC' => esc_html__( 'Descending Order', 'super-forms' )
            ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'post_type'
        );
    }
    public static function sf_retrieve_method_exclude_taxonomy($value, $parent){
        return array(
            'name' => esc_html__( 'Exclude a category', 'super-forms' ), 
            'label' => esc_html__( 'Enter the category ID\'s to exclude seperated by comma\'s', 'super-forms' ), 
            'default'=> ( !isset( $value ) ? '' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'taxonomy'
        );
    }
    public static function sf_retrieve_method_exclude_post($value, $parent){
        return array(
            'name' => esc_html__( 'Exclude a post', 'super-forms' ), 
            'label' => esc_html__( 'Enter the post ID\'s to exclude seperated by comma\'s', 'super-forms' ), 
            'default'=> ( !isset( $value ) ? '' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'post_type'
        );
    }                        
    public static function sf_retrieve_method_filters($value, $parent){
        return array(
            'type' => 'textarea',
            'name' => esc_html__( 'Filter posts by specific taxonomy', 'super-forms' ),
            'label' => sprintf( esc_html__('Define each taxonomy filter on a new line e.g: %1$s%3$sfield|value1,value2,value3|taxonomy|operator%2$s%3$sPossible values for the operator are %1$sIN%2$s, %1$sNOT IN%2$s, %1$sAND%2$s, %1$sEXISTS%2$s and %1$sNOT EXISTS%2$s%3$sExample to create a filter based of ID for Post category:%3$s%1$sid|8429|category|IN%2$s%3$sExample to create a filter based of slug for Post category:%3$s%1$sslug|cars|category|IN%2$s%3$sExample to create a filter based of ID for Post tags:%3$s%1$sid|8429|post_tag|IN%2$s%3$sExample to create a filter based of slug for Post tags:%3$s%1$sslug|red|post_tag|IN%2$s%3$sExample to create a filter based of ID for WC product category:%3$s%1$sid|8429|product_cat|IN%2$s%3$sExample to create a filter based of slug for WC product category:%3$s%1$sslug|cars|product_cat|IN%2$s%3$sExample to create a filter based of ID for WC product tags:%3$s%1$sid|8429|product_tag|IN%2$s%3$sExample to create a filter based of slug for WC product tags:%3$s%1$sslug|red|product_tag|IN%2$s', 'super-forms'), '<strong style="color:red;">', '</strong>', '<br />' ),
            'default'=> ( !isset( $value ) ? '' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'post_type'
        );
    }
    public static function sf_retrieve_method_filter_relation($value, $parent){
        return array(
            'name' => esc_html__( 'Filters relation', 'super-forms' ), 
            'label' => esc_html__( 'Select a filter relation (OR|AND)', 'super-forms' ), 
            'default'=> ( !isset( $value ) ? 'OR' : $value ),
            'type' => 'select', 
            'values' => array(
                'OR' => 'OR (' . esc_html__( 'default', 'super-forms' ) .')', 
                'AND' => 'AND'
            ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'post_type'
        );
    }
    public static function sf_retrieve_method_hide_empty($value, $parent){
        return array(
            'name' => esc_html__( 'Hide empty categories', 'super-forms' ), 
            'label' => esc_html__( 'Show or hide empty categories', 'super-forms' ), 
            'default'=> ( !isset( $value ) ? 0 : $value ),
            'type' => 'select', 
            'filter'=>true,
            'values' => array(
                0 => esc_html__( 'Disabled', 'super-forms' ), 
                1 => esc_html__( 'Enabled', 'super-forms' ),
            ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'taxonomy'
        );
    }
    public static function sf_retrieve_method_parent($value, $parent){
        return array(
            'name' => esc_html__( 'Based on parent ID', 'super-forms' ), 
            'label' => esc_html__( 'Retrieve categories by it\'s parent ID (integer only)', 'super-forms' ), 
            'default'=> ( !isset( $value ) ? '' : $value ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'taxonomy,post_type'
        );
    }                          
    public static function sf_retrieve_method_value($value, $parent){
        return array(
            'name' => esc_html__( 'Retrieve Slug, ID, Title or Meta Data as value', 'super-forms' ), 
            'label' => esc_html__( 'Select if you want to retrieve slug, ID or the title as value', 'super-forms' ), 
            'default'=> ( !isset( $value ) ? 'slug' : $value ),
            'type' => 'select', 
            'values' => array(
                'slug' => esc_html__( 'Slug (default)', 'super-forms' ), 
                'id' => esc_html__( 'ID', 'super-forms' ),
                'title' => esc_html__( 'Title', 'super-forms' ),
                'custom' => esc_html__( 'Custom post meta data', 'super-forms' ),
            ),
            'filter'=>true,
            'parent'=>$parent,
            'filter_value'=>'taxonomy,post_type,tags'
        );
    }
    public static function sf_retrieve_method_meta_keys($value){
        return array(
            'type' => 'textarea',
            'name' => esc_html__( 'Define meta data to return as value', 'super-forms' ), 
            'label' => esc_html__( "Put each meta key on a new line, for instance if you want to return the ID, image, price and title of a product, you could enter:\n ID\nfeatured_image\npost_title\n_regular_price\n\nWhen retrieving the value in the form dynamically you can use tags like so: {fieldname;1} (to retrieve the ID), {fieldname;2} (to retrieve the image URL), {fieldname;3} (for title), and {fieldname;4} (to retrieve the price)", 'super-forms' ),
            'placeholder' => "ID\n_regular_price",
            'default'=> ( !isset( $value ) ? '' : $value ),
            'filter'=>true,
            'parent'=>'retrieve_method_value',
            'filter_value'=>'custom'
        );
    }


    /** 
     *  The form shortcode that will generate the form with all it's elements/fields
     *
     * @param  array $atts
     * @param  boolean $elements_only  @since 4.7.0 - used for translation language switcher to reload form with translated elements/fields
     *
     *  @since      1.0.0
    */
    public static function super_form_func( $atts, $elements_only=false ) {
        
        if( class_exists( 'SUPER_WooCommerce' ) ){
            remove_action( 'pre_get_posts', array( SUPER_WooCommerce(), 'exclude_products_from_shop' ) );
        }

        // @since 2.1.0 - make sure we reset the grid system
        unset($GLOBALS['super_grid_system']);

        extract( shortcode_atts( array(
            'id' => '',
            'list_id' => '',
            'entry_id' => '',
            'i18n' => ''
        ), $atts ) );

        $editingContactEntry = false;
        if($id!=='' && $list_id!=='' && $entry_id!==''){
            $editingContactEntry = true;
        }

        // @since 4.6.0 - set GET parameters for parsed shortcode params
        foreach($atts as $k => $v){
            // Skip the default "id" parameter
            if($k!='id'){
                // Only save the value if it doesn't exist yet
                // This way it allows us to override any params through URL parameters
                if(!isset($_GET[$k])){
                    $_GET[$k] = $v;
                }
            }
        }
        // The following is required when user switches between languages on the front-end
        if(isset($atts['parameters']) && is_array($atts['parameters'])){
            foreach($atts['parameters'] as $k => $v){
                if(!isset($_GET[$k])){
                    $_GET[$k] = $v;
                }
            }
        }

        // Sanitize the ID
        $form_id = absint($id);

        self::$current_form_id = $form_id;

        // Check if the post exists
        if ( FALSE === get_post_status( $form_id ) ) {
            // The post does not exist
            $result = '<strong>'.esc_html__('Error', 'super-forms' ).':</strong> '.sprintf(esc_html__('Super Forms could not find a form with ID: %d', 'super-forms' ), $form_id);
            return $result;
        }else{
            // Check if the post is a super_form post type
            $post_type = get_post_type($form_id);
            if( $post_type!='super_form' ) {
                    $result = '<strong>'.esc_html__('Error', 'super-forms' ).':</strong> '.sprintf(esc_html__('Super Forms could not find a form with ID: %d', 'super-forms' ), $form_id);
                    return $result;
            }
        }

        /** 
         *  Make sure that we have all settings even if this form hasn't saved it yet when new settings where added by a add-on
         *
         *  @since      1.0.6
        */
        require_once( SUPER_PLUGIN_DIR . '/includes/class-settings.php' );

        $settings = SUPER_Common::get_form_settings($form_id);
        // Allow users to override any form settings through the shortcode, this way you can have a single form that can be used many times with different settings
        foreach($atts as $k => $v){
            // If the shortcode attribute starts with "_setting_" we use it 
            // e.g.: `_setting_retrieve_last_entry_data="false"` will set the option to "false", even if it was set to "true"
            if(substr( $k, 0, 9 )==='_setting_'){
                // Either override existing setting or set new
                $settingKey = substr($k, 9, strlen($k));
                $settings[substr($k, 9, strlen($k))] = $v;
            }
        }
        // Allow us to manipulate form settings, currently used by Listings Add-on
        $settings = apply_filters( 'super_before_form_render_settings_filter', $settings, array( 'id' => $id, 'list_id' => $list_id, 'entry_id' => $entry_id, 'i18n' => $i18n ) );        

        $translations = SUPER_Common::get_form_translations($form_id);

        // @since 4.7.0 - translation
        if(!empty($i18n)){
            $i18n = sanitize_text_field($i18n);
            if( (!empty($settings['i18n'])) && (!empty($settings['i18n'][$i18n])) ){
                $settings = array_replace_recursive($settings, $settings['i18n'][$i18n]);
                unset($settings['i18n']);
            }
        }

        SUPER_Forms()->enqueue_element_styles();
        SUPER_Forms()->enqueue_element_scripts($settings, false, $form_id);

        $styles = '';

        // @since 1.3
        if( !empty( $settings['theme_form_margin'] ) ) {
            $styles .= 'margin:' . $settings['theme_form_margin'] . ';';
        }

        if($styles!='') $styles = 'style="' . $styles . '" ';

        // Try to load the selected theme style
        // Always load the default styles
        $style_content = require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/style-default.php' );
        $style_content .= require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/fonts.php' );
        $style_content .= require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/colors.php' );
        $class = ' super-default-squared';
        if(!empty($settings['theme_style'])) {
            $class = ' ' . $settings['theme_style'];
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
        }else{
            // @since 4.7.0 - translation RTL
            // check if the translation has enable RTL mode
            $translations = SUPER_Common::get_form_translations($form_id);
            if(is_array($translations)){
                if( !empty($translations[$i18n]) && !empty($translations[$i18n]['rtl']) ){
                    if($translations[$i18n]['rtl']=='true'){
                        $class .= ' super-rtl';
                    }
                }
            }
        }

        // @since 3.6.0     - Center form
        if( !empty( $settings['theme_center_form'] ) ) {
            $class .= ' super-center-form';
        }

        // @since 3.2.0     - Save form progress
        if( !empty( $settings['save_form_progress'] ) ) {
            $class .= ' super-save-progress';
        }

        // @since 4.9.3     - Adaptive placeholders
        if($settings['theme_style']=='super-style-one') $settings['enable_adaptive_placeholders'] = '';
        if( !empty($settings['enable_adaptive_placeholders']) ) {
            $class .= ' super-adaptive';
        } 
 
        $entry_data = null;

        $contact_entry_id = 0;
        if( isset( $_GET['contact_entry_id'] ) ) {
            $contact_entry_id = absint($_GET['contact_entry_id']);
        }else{
            if( isset( $_POST['contact_entry_id'] ) ) {
                $contact_entry_id = absint($_POST['contact_entry_id']);
            }
        }
        if($contact_entry_id!=0){
            // If Admin, we are allowed to access this data directly
            if( current_user_can('administrator') ) {
                $entry_data = get_post_meta( $contact_entry_id, '_super_contact_entry_data', true );
            }else{
                // User must be logged in to access this data, or transient with entry ID should be available
                $authenticated_entry_id = get_transient( 'super_form_authenticated_entry_id_' . $contact_entry_id );
                if($authenticated_entry_id!==false){
                    $entry_data = get_post_meta( $authenticated_entry_id, '_super_contact_entry_data', true );
                    delete_transient( 'super_form_authenticated_entry_id_' . $contact_entry_id );
                }else{
                    $current_user_id = get_current_user_id();
                    if( $current_user_id!=0 ) {
                        // By default retrieve entry data based on this form ID
                        $form_ids = array($form_id);
                        // Check if we are retrieving entry data based on other form ID
                        if( ( isset( $settings['retrieve_last_entry_form'] ) ) && ( $settings['retrieve_last_entry_form']!='' ) ) {
                            $form_ids = explode( ",", $settings['retrieve_last_entry_form'] );
                        }
                        $form_ids = implode("','", $form_ids);
                        // Lookup contact entries based on this user ID and form ID(s)
                        global $wpdb;
                        $table = $wpdb->prefix . 'posts';
                        $table_meta = $wpdb->prefix . 'postmeta';
                        $entry = $wpdb->get_results("
                        SELECT  ID 
                        FROM    $table 
                        WHERE   post_author = $current_user_id AND
                                post_parent IN ('$form_ids') AND
                                post_status IN ('publish','super_unread','super_read') AND 
                                post_type = 'super_contact_entry'
                        ORDER BY ID DESC
                        LIMIT 1");
                        if( isset($entry[0])) {
                            $entry_data = get_post_meta( $entry[0]->ID, '_super_contact_entry_data', true );
                        }
                    }
                }
            }
        }
        // @since 2.9.0 - autopopulate form with user last submitted entry data
        if( ( isset( $settings['retrieve_last_entry_data'] ) ) && ( $settings['retrieve_last_entry_data']=='true' ) ) {

            // @since 3.8.0 - retrieve entry data based on $_GET['contact_entry_id'] or $_POST['contact_entry_id'] 
            if( empty($contact_entry_id) ) {
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
                            post_parent IN ('$form_ids') AND
                            post_status IN ('publish','super_unread','super_read') AND 
                            post_type = 'super_contact_entry'
                    ORDER BY ID DESC
                    LIMIT 1");
                    if( isset($entry[0])) {
                        // If entry exists, set the ID so we can actually update it based on the ID later
                        if( !empty($settings['update_contact_entry']) ) {
                            $contact_entry_id = absint($entry[0]->ID);
                        }
                        $entry_data = get_post_meta( $entry[0]->ID, '_super_contact_entry_data', true );
                    }
                }
            }
        }
        if(!empty($entry_data)){
            unset($entry_data['hidden_form_id']);
        }

        // @since 3.2.0 - if entry data was not found based on user last entry, proceed and check if we need to get form progress for this form
        $formProgress = false;
        if( ($entry_data==null) && ( (isset($settings['save_form_progress'])) && ($settings['save_form_progress']=='true') ) ) {
            //$form_progress = SUPER_Common::getClientData( 'super_form_progress_' . $form_id );
            $form_progress = SUPER_Common::getClientData( 'progress_' . $form_id );
            if($form_progress!=false){
                $entry_data = $form_progress;
                $formProgress = true;
            }
        }

        $result = '';
        $result .= SUPER_Common::load_google_fonts($settings);
        if(!$elements_only){
            $result .= '<style type="text/css">.super-form:not(.super-initialized) *:not(.super-load-icon) { visibility: hidden !important; }</style>';
            $result .= '<div id="super-form-' . $form_id . '" '; 
            $result .= $styles;
            $result .= 'class="super-form ';
            $result .= ( $settings['form_preload'] == 0 ? 'preload-disabled ' : '' );
            $result .= 'super-form-' . $form_id;
            $result .= ' ' . $class;
            $result .= '"';
            $result .= ( (isset($settings['form_hide_after_submitting'])) && ($settings['form_hide_after_submitting']=='true') ? ' data-hide="true"' : '' );
            $result .= ( (isset($settings['form_clear_after_submitting'])) && ($settings['form_clear_after_submitting']=='true') ? ' data-clear="true"' : '' );
            $result .= ( (isset($settings['form_processing_overlay'])) && ($settings['form_processing_overlay']=='true') ? ' data-overlay="true"' : '' );
            $result .= ( (!empty($settings['multipart_url_params'])) && ($settings['multipart_url_params']==='false') ? ' data-step-params="false"' : '' );

            
            // @since 3.3.0     - Disable submission on "Enter" 
            $result .= ( (isset($settings['form_disable_enter'])) && ($settings['form_disable_enter']=='true') ? ' data-disable-enter="true"' : '' );

            $result .= ' data-field-size="' . $settings['theme_field_size'] . '"';
            
            // @since 4.7.0 - translation
            if(!empty($i18n)){
                $result .= ' data-i18n="' . $i18n . '"';
            }
            $result .= '>';
            
            // @since 4.7.0 - improved method to center form and to give max width to the form
            if( !empty( $settings['theme_max_width'] ) ) {
                $result .= '<div class="super-max-width-wrapper" style="max-width:' . $settings['theme_max_width'] . 'px;">';
            }

            // @since 3.0.0 - new loading method (gif stops/freezes animating when browser is doing javascript at background)
            $result .= '<span class="super-load-icon"></span>';




            // @since 4.7.0 - translation langauge switcher
            if(empty($settings['i18n_switch'])) $settings['i18n_switch'] = 'false';
            if(empty($i18n) && $settings['i18n_switch']=='true'){
                $translations = SUPER_Common::get_form_translations($form_id);
                if(!empty($translations) && is_array($translations) && count($translations)>1 ){
                    wp_enqueue_style( 'super-flags', SUPER_PLUGIN_FILE . 'assets/css/frontend/flags.css', array(), SUPER_VERSION );    
                    $default_language = current($translations);
                    // Set default language to current language if not empty
                    if(!empty($i18n)) $default_language = $translations[$i18n];

                    $result .= '<div class="super-i18n-switcher">';
                    foreach($translations as $tk => $tv){
                        $translations[$tk]['checked'] = 'false';
                        $translations[$tk]['value'] = $tk;
                        $translations[$tk]['label'] = $tv['language'];
                        $translations[$tk]['flag'] = $tv['flag'];
                        if($default_language['flag'] == $tv['flag']){
                            $translations[$tk]['checked'] = 'true';
                        }
                    }
                    $result .= self::dropdown(array(
                        'tag'=>'dropdown',
                        'atts'=>array(
                            'language_switch' => true,
                            'default_language' => $default_language,
                            'name' => 'title',
                            'email' => 'Title:',
                            'dropdown_items' => $translations,
                            'placeholder' => '- select your language -',
                            'placeholderFilled' => '- select your language -',
                            'validation' => 'none',
                            'absolute_default' => ''
                        ),
                        'settings'=>$settings, 
                        'i18n'=>$i18n
                    ));
                    $result .= '</div>';
                }
            }

            // @since 1.8 - needed for autocomplete
            $result .= '<form tabindex="0" autocomplete="on"';
            $enctype = apply_filters( 'super_form_enctype_filter', 'multipart/form-data', array( 'id'=>$form_id, 'settings'=>$settings ) );
            if( !empty($enctype) ) {
                $result .= ' enctype="' . esc_attr($enctype) . '"';
            }

            // @since 3.6.0 - custom POST parameters method
            if( empty($settings['form_post_custom']) ) $settings['form_post_custom'] = '';

            // @since 2.2.0 - custom POST method
            if( ( isset( $settings['form_post_option'] ) ) && ( $settings['form_post_option']=='true' ) && ( $settings['form_post_custom']!='true' ) ) {
                $result .= ' method="post" action="' . $settings['form_post_url'] . '" data-actiontags="' . $settings['form_post_url'] . '">';
                $result .= '<textarea class="super-hidden" name="json_data"></textarea>';
            }else{
                $result .= '>';
            }
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
                if(!empty($settings['form_locker_msg'])) {
                    $result .= '<div class="super-msg super-error">';
                    if($settings['form_locker_msg_title']!='') {
                        $result .= '<h1>' . $settings['form_locker_msg_title'] . '</h1>';
                    }
                    $result .= nl2br($settings['form_locker_msg_desc']);
                    $result .= '<span class="super-close"></span>';
                    $result .= '</div>';
                    if($settings['form_locker_hide']=='true') {
                        $result .= '</form>';
                        $result .= '</div>';
                        return $result;
                    }
                }else{
                    // Do not display anything
                    return '';
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
                    if(!empty($settings['user_form_locker_msg'])) {
                        $result .= '<div class="super-msg super-error">';
                        if(!empty($settings['user_form_locker_msg_title'])) {
                            $result .= '<h1>' . $settings['user_form_locker_msg_title'] . '</h1>';
                        }
                        $result .= nl2br($settings['user_form_locker_msg_desc']);
                        $result .= '<span class="super-close"></span>';
                        $result .= '</div>';
                        if(!empty($settings['user_form_locker_hide'])) {
                            $result .= '</form>';
                            $result .= '</div>';
                            return $result;
                        }
                    }else{
                        // Do not display anything
                        return '';
                    }
                }
            }
        }

        // Display message to admin if the form is in debug mode for PDF generator
        if(!empty($settings['_pdf'])) {
            if($settings['_pdf']['generate']==='true' && $settings['_pdf']['debug']==='true') {
                $result .= '<div class="super-msg super-info" data-pdfoption="exclude">';
                    if($settings['form_locker_msg_title']!='') {
                        $result .= '<h1>'.esc_html__( 'PDF Generator debug mode is enabled!', 'super-forms' ).'</h1>';
                    }
                    $result .= esc_html__( 'The form will not be submitted, no email will be send and no Contact Entry will be saved. Only the PDF file will be generated and downloaded for testing purposes.', 'super-forms' );
                    $result .= '<span class="super-close"></span>';
                $result .= '</div>';
            }
        }
        
        // @since 4.6.0 - add nonce field
        $result .= '<input type="hidden" name="sf_nonce" value="" />';
        
        // @since 3.2.0 - add honeypot captcha
        $result .= '<input type="text" name="super_hp" size="25" value="" />';

        // @since 3.1.0 - filter to add any HTML before the first form element
        $result = apply_filters( 'super_form_before_first_form_element_filter', $result, array( 'id'=>$form_id, 'settings'=>$settings ) );

        $result .= '<div class="super-shortcode super-field super-hidden">';
        $result .= '<input class="super-shortcode-field" type="hidden" value="' . absint($form_id) . '" name="hidden_form_id" />';
        $result .= '</div>';

        // When editing a contact entry, we need to pass the below values to handle the ajax request
        if($editingContactEntry===true){
            // Holds list ID
            $result .= '<div class="super-shortcode super-field super-hidden">';
            $result .= '<input class="super-shortcode-field" type="hidden" value="' . absint($list_id) . '" name="hidden_list_id" />';
            $result .= '</div>';
        }

        // Grab all form elements
        $elements = get_post_meta( $form_id, '_super_elements', true );
        if( !is_array($elements) ) {
            $elements = json_decode( $elements, true );
        }

        // @since 2.2.0 - update contact entry by ID
        if( (isset( $settings['update_contact_entry'] )) && ($settings['update_contact_entry']=='true') ) {
            
            // @since 4.7.7 - only add this field if no such field name already exists
            // otherwise we would end up with duplicate fields
            $re = '/{"name":"hidden_contact_entry_id"/';
            $str = json_encode($elements);
            preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
            if(empty($matches)){
                $result .= '<div class="super-shortcode super-field super-hidden">';
                    $result .= '<input class="super-shortcode-field" type="hidden" value="' . absint($contact_entry_id) . '" name="hidden_contact_entry_id" />';
                $result .= '</div>';
            }
        }

        // Loop through all form elements
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
                $result .= self::output_element_html( array('grid'=>null, 'tag'=>$v['tag'], 'group'=>$v['group'], 'data'=>$v['data'], 'inner'=>$v['inner'], 'shortcodes'=>$shortcodes, 'settings'=>$settings, 'i18n'=>$i18n, 'builder'=>false, 'entry_data'=>$entry_data, 'dynamic'=>0, 'dynamic_field_names'=>array(), 'inner_field_names'=>array(), 'formProgress'=>$formProgress) );
            }
        }
        
        // Make sure to only return the default submit button if no custom button was used
        if(!isset($GLOBALS['super_custom_button_used'])){
            $result .= self::button( array( 'tag'=>'button', 'atts'=>array(), 'settings'=>$settings, 'i18n'=>$i18n));
        }
        // In case of editing an entry via Listings Add-on, make sure we add a submit button
        if( !isset($GLOBALS['super_submit_button_found']) && $editingContactEntry===true ){
            $result .= self::button( array( 'tag'=>'button', 'atts'=>array( 'name'=>esc_html__( 'Update', 'super-forms' )), 'settings'=>$settings, 'i18n'=>$i18n));
        }

        // Always unset after all elements have been processed
        unset($GLOBALS['super_custom_button_used']);
        unset($GLOBALS['super_submit_button_found']);
        unset($GLOBALS['super_first_multipart']); // @since 2.6.0

        // @since 3.1.0 - filter to add any HTML after the last form element
        $result = apply_filters( 'super_form_after_last_form_element_filter', $result, array( 'id'=>$form_id, 'settings'=>$settings ) );

        if(!$elements_only){
            $result .= '</form>';
 
            // @since 4.7.0 - improved method to center form and to give max width to the form
            if( !empty( $settings['theme_max_width'] ) ) {
                $result .= '</div>';
            }

            $result .= '</div>';

            // @since 1.3   - put styles in global variable and append it to the footer at the very end
            SUPER_Forms()->form_custom_css .= apply_filters( 'super_form_styles_filter', $style_content, array( 'id'=>$form_id, 'settings'=>$settings ) );

            // @since 1.2.8     - Custom CSS per Form
            if( !isset( $settings['form_custom_css'] ) ) $settings['form_custom_css'] = '';
            $settings['form_custom_css'] = stripslashes($settings['form_custom_css']);
            SUPER_Forms()->form_custom_css .= $settings['form_custom_css'];

            // @since 4.2.0 - custom JS script
            $js = '';
            if( !empty(SUPER_Forms()->theme_custom_js) ) {
                $js .= SUPER_Forms()->theme_custom_js;
            }
            $global_settings = SUPER_Common::get_global_settings();
            if( !empty($global_settings['theme_custom_js']) ) {
                $js .= $global_settings['theme_custom_js'];
            }
            SUPER_Forms()->theme_custom_js = apply_filters( 'super_form_js_filter', $js, array( 'id'=>$form_id, 'settings'=>$settings ) );

            $result = apply_filters( 'super_form_before_do_shortcode_filter', $result, array( 'id'=>$form_id, 'settings'=>$settings ) );

        }

        if( class_exists( 'SUPER_WooCommerce' ) ){
            add_action( 'pre_get_posts', array( SUPER_WooCommerce(), 'exclude_products_from_shop' ) );
        }

        // If on back-end builder page
        if( !empty($_POST['action']) && $_POST['action'] === 'super_load_preview' ){
            $css = SUPER_Forms()->form_custom_css;
            $global_css = '';
            if( isset(SUPER_Forms()->global_settings) ) {
                if( isset(SUPER_Forms()->global_settings['theme_custom_css']) ) {
                    $global_css = stripslashes(SUPER_Forms()->global_settings['theme_custom_css']);
                }
            }
            if( $css!='' ) $result .= '<style type="text/css">' . $global_css . $css . '</style>';
        }

        // Load PDF Generator fonts (only if enabled)
        if(!empty($settings['_pdf'])) {
            if($settings['_pdf']['generate']==='true') {
                // When value is not set, but PDF is activated, set it to true, to not break existing forms that might require it
                if(!isset($settings['_pdf']['textRendering'])) $settings['_pdf']['textRendering'] = 'true';
                if(!isset($settings['_pdf']['cyrillicText'])) $settings['_pdf']['cyrillicText'] = 'true'; 
                if(!isset($settings['_pdf']['arabicText'])) $settings['_pdf']['arabicText'] = 'false'; 
                // Only if text rendering is enabled, and cyrillic text is enabled
                if($settings['_pdf']['textRendering']==='true'){
                    // Cyrillic compatible font
                    if($settings['_pdf']['cyrillicText']==='true') {
                        add_action( 'wp_footer', function(){
                            ?>
                            <script>
                            super_common_i18n.fonts = {
                                NotoSans: JSON.parse('<?php echo file_get_contents(SUPER_PLUGIN_DIR . '/includes/extensions/pdf-generator/fonts.json'); ?>')
                            };
                            </script>
                            <?php
                        }, 100 );
                    }
                    // Arabic compatible font
                    if($settings['_pdf']['arabicText']==='true') {
                        add_action( 'wp_footer', function(){
                            ?>
                            <script>
                            super_common_i18n.fonts = {
                                NotoSans: JSON.parse('<?php echo file_get_contents(SUPER_PLUGIN_DIR . '/includes/extensions/pdf-generator/fonts-arabic.json'); ?>')
                            };
                            </script>
                            <?php
                        }, 100 );
                    }
                }
            }
        }

        return do_shortcode( $result );
    }

}

endif;
