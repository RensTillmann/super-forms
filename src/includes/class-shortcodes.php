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
     *  Get all items by post_type
     *
     *  @since      1.0.0
    */
    public static function get_items($items=array(), $tag, $atts, $prefix='', $settings=array(), $entry_data=array()){

        // When advanced tags is being used get the first value
        if(!empty($atts['value'])) $real_value = explode(';', $atts['value'])[0];       

        // First retrieve all the values from URL parameter
        $selected_items = array();
        if($tag==='dropdown' || $tag==='checkbox'){
            // When advanced tags is being used get the first value
            if(!empty($atts['value'])) $selected_items = explode( ",", $atts['value'] );
        }
        if($tag==='radio'){
            if(!empty($atts['value'])) $selected_items = array($atts['value']);
        }

        // Now get all the actual values (in case user is using dynamic values like: 1;Red)
        $selected_values = array();
        foreach($selected_items as $k => $v){
            // Make sure to trime the values
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
                    if( ($v['checked']=='true' || $v['checked']==1) ) {
                        $class .= 'super-default-selected';
                    }
                    if( empty($selected_values) ) {
                        if( ($v['checked']=='true' || $v['checked']==1) ) {
                            $class .= ' super-active';
                        }
                    }else{
                        if(in_array( $real_value, $selected_values ) ) {
                            $class .= ' super-active';
                            $placeholder[] = $v['label'];
                        }
                    }
                    $items[] = '<li ' . ( !empty($class) ? 'class="'.$class.'" ' : '') . 'data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '">' . stripslashes($v['label']) . '</li>'; 
                    $items_values[] = $v['value'];
                }
            }
            if($tag==='text'){
                // text - autosuggest - custom
                if( !empty($atts['enable_auto_suggest']) ) {
                    if( ( isset( $atts['autosuggest_items'] ) ) && ( count($atts['autosuggest_items'])!=0 ) && ( $atts['autosuggest_items']!='' ) ) {
                        $items = array();
                        foreach( $atts['autosuggest_items'] as $k => $v ) {
                            if( $v['checked']=='true' || $v['checked']==1 ) {
                                $selected_items[] = $v['value'];
                                $atts['value'] = $v['value'];
                                $items[] = '<li data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '" class="super-active super-default-selected">' . stripslashes($v['label']) . '</li>'; 
                            }else{
                                $items[] = '<li ' . ($atts['value']==$v['value'] ? 'class="super-active" ' : '') . 'data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '">' . stripslashes($v['label']) . '</li>'; 
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
                            if( $v['checked']=='true' || $v['checked']==1 ) {
                                $selected_items[] = $v['value'];
                                $item = '<li class="super-active" data-value="' . esc_attr($v['value']) . '" data-search-value="' . esc_attr($v['label']) . '">';
                            }else{
                                $item = '<li data-value="' . esc_attr($v['value']) . '" data-search-value="' . esc_attr($v['label']) . '">';
                            }
                            $item .= '<span class="super-wp-tag">' . stripslashes($v['label']) . '</span>'; 
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
                    $class = '';
                    // Check if this should be remembered as the default value set via settings
                    if( ($v['checked']=='true' || $v['checked']==1) ) {
                        $class .= 'super-default-selected';
                    }
                    if( empty($selected_values) ) {
                        if( ($v['checked']=='true' || $v['checked']==1) ) {
                            $class .= ' super-active';
                        }
                    }else{
                        if(in_array( $real_value, $selected_values ) ) {
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
                            $item .= '<div class="image" style="background-image:url(\'' . $image . '\');"><img src="' . $image . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                        }else{
                            $image = SUPER_PLUGIN_FILE . 'assets/images/image-icon.png';
                            $item .= '<div class="image" style="background-image:url(\'' . $image . '\');"><img src="' . $image . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                        }
                        $item .= '<input type="checkbox" value="' . esc_attr( $v['value'] ) . '" />';
                        if($v['label']!='') $item .= '<span class="super-item-label">' . stripslashes($v['label']) . '</span>';
                        $item .='</label>';
                    }else{
                        $item = '<label ' . ( !empty($class) ? 'class="'.$class.'" ' : '') . '><input type="checkbox" value="' . esc_attr( $v['value'] ) . '" />' . stripslashes($v['label']) . '</label>';
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
                    $class = '';
                    // Check if this should be remembered as the default value set via settings
                    if( ($v['checked']=='true' || $v['checked']==1) ) {
                        $class .= 'super-default-selected';
                    }
                    if( empty($selected_values) ) {
                        if( $found==false && ($v['checked']=='true' || $v['checked']==1) ) {
                            $selected_items[] = $v['value'];
                            $found = true;
                            $class .= ' super-active';
                        }
                    }else{
                        if( $found==false && in_array( $real_value, $selected_values ) ) {
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
                            $item .= '<div class="image" style="background-image:url(\'' . $image . '\');"><img src="' . $image . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                        }else{
                            $image = SUPER_PLUGIN_FILE . 'assets/images/image-icon.png';
                            $item .= '<div class="image" style="background-image:url(\'' . $image . '\');"><img src="' . $image . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                        }
                        $item .= '<input type="radio" value="' . esc_attr( $v['value'] ) . '" />';
                        if($v['label']!='') $item .= '<span class="super-item-label">' . stripslashes($v['label']) . '</span>';
                        $item .='</label>';
                    }else{
                        $item = '<label ' . ( !empty($class) ? 'class="'.$class.'" ' : '') . '><input type="radio" value="' . esc_attr( $v['value'] ) . '" />' . stripslashes($v['label']) . '</label>';
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
                        $items[] = '<li data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr($v->name) . '"><span class="super-wp-tag">' . $v->name . '</span></li>';
                    }else{
                        // text - autosuggest - taxonomy
                        $items[] = '<li data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr( $v->name ) . '">' . $v->name . '</li>'; 
                    }
                }   
                // dropdown - taxonomy
                if($tag=='dropdown')    $items[] = '<li data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr( $v->name ) . '">' . $v->name . '</li>'; 
                // checkbox - taxonomy
                if($tag=='checkbox')    $items[] = '<label class="' . ( !in_array($data_value, $selected_items) ? '' : 'super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input' . ( !in_array($data_value, $selected_items) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $data_value ) . '" />' . $v->name . '</label>';
                // radio - taxonomy
                if($tag=='radio')       $items[] = '<label class="' . ( ($atts['value']!=$data_value) ? '' : 'super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input type="radio" value="' . esc_attr( $data_value ) . '" />' . $v->name . '</label>';
                $items_values[] = $data_value;
            }
        }

        // dropdown - post_type
        // checkbox - post_type
        // radio - post_type
        // text - autosuggest - post_type
        // text - keywords - post_type
        if($atts['retrieve_method']=='post_type') {
            if( !isset( $atts[$prefix.'retrieve_method_post'] ) ) $atts[$prefix.'retrieve_method_post'] = 'post';
            if( !isset( $atts[$prefix.'retrieve_method_post_status'] ) ) $atts[$prefix.'retrieve_method_post_status'] = 'publish';
            if( !isset( $atts[$prefix.'retrieve_method_exclude_post'] ) ) $atts[$prefix.'retrieve_method_exclude_post'] = '';
            if( !isset( $atts[$prefix.'retrieve_method_parent'] ) ) $atts[$prefix.'retrieve_method_parent'] = '';
            if( !isset( $atts[$prefix.'retrieve_method_orderby'] ) ) $atts[$prefix.'retrieve_method_orderby'] = 'title';
            if( !isset( $atts[$prefix.'retrieve_method_order'] ) ) $atts[$prefix.'retrieve_method_order'] = 'ASC';
            $args = array(
                'post_type' => $atts[$prefix.'retrieve_method_post'],
                'post_status' => $atts[$prefix.'retrieve_method_post_status'],
                'exclude' => $atts[$prefix.'retrieve_method_exclude_post'],
                'post_parent' => $atts[$prefix.'retrieve_method_parent'],
                'orderby' => $atts[$prefix.'retrieve_method_orderby'],
                'order' => $atts[$prefix.'retrieve_method_order'],
                'posts_per_page' => -1, 
                'numberposts' => -1
            );
            // Check if we need to filter based on taxonomy
            if(!empty($atts[$prefix.'retrieve_method_filters'])){
                // Make sure we grab the tag ID and then add it to the array
                $filters = explode("\n", $atts[$prefix.'retrieve_method_filters']);
                $tax_query = array(
                    'relation' => (!empty($atts[$prefix.'retrieve_method_filter_relation']) ? $atts[$prefix.'retrieve_method_filter_relation'] : 'IN')
                );
                foreach($filters as $fv){
                    $params = explode("|", $fv);
                    if(isset($params[0]) && isset($params[0]) && isset($params[0])) {
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
                                    $image_url = $attachment_image[0];
                                    if($rk>0){
                                        $data_value .= ';'.$image_url;
                                    }else{
                                        $data_value .= $image_url;
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
                        if($tag=='text') {
                            if($prefix=='keywords_'){
                                $items[] = '<li data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr( $vv['post_title']) . '"><span class="super-wp-tag">' . $vv['post_title'] . '</span></li>';
                            }else{
                                $items[] = '<li data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr( $vv['post_title'] ) . '">' . $vv['post_title'] . '</li>'; 
                            }
                        }   
                        if($tag=='dropdown')    $items[] = '<li data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr( $vv['post_title'] ) . '">' . $vv['post_title'] . '</li>'; 
                        if($tag=='checkbox')    $items[] = '<label class="' . ( !in_array($data_value, $selected_items) ? '' : 'super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input' . ( !in_array($data_value, $selected_items) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $data_value ) . '" />' . $vv['post_title'] . '</label>';
                        if($tag=='radio')       $items[] = '<label class="' . ( ($atts[$prefix.'value']!=$data_value) ? '' : 'super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input type="radio" value="' . esc_attr( $data_value ) . '" />' . $vv['post_title'] . '</label>';
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
                                $image_url = $attachment_image[0];
                                if($rk>0){
                                    $data_value .= ';'.$image_url;
                                }else{
                                    $data_value .= $image_url;
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
                            $items[] = '<li data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr( $v['post_title']) . '"><span class="super-wp-tag">' . $v['post_title'] . '</span></li>';
                        }else{
                            $items[] = '<li ' . ( $atts['value']==explode(';', $data_value)[0] ? 'class="super-active" ' : '' ) . 'data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr( $v['post_title'] ) . '">' . $v['post_title'] . '</li>';
                            $item_value = explode(';', $data_value)[0];
                        }
                    }
                    if($tag=='dropdown')    $items[] = '<li data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr( $v['post_title'] ) . '">' . $v['post_title'] . '</li>'; 
                    if($tag=='checkbox')    $items[] = '<label class="' . ( !in_array($data_value, $selected_items) ? '' : 'super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input' . ( !in_array($data_value, $selected_items) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $data_value ) . '" />' . $v['post_title'] . '</label>';
                    if($tag=='radio')       $items[] = '<label class="' . ( ($atts[$prefix.'value']!=$data_value) ? '' : 'super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input type="radio" value="' . esc_attr( $data_value ) . '" />' . $v['post_title'] . '</label>';
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
                                    $items[] = '<li data-value="' . esc_attr( $v ) . '" data-search-value="' . esc_attr( $v ) . '"><span class="super-wp-tag">' . $v . '</span></li>'; 
                                }else{
                                    $items[] = '<li data-value="' . esc_attr( $v ) . '" data-search-value="' . esc_attr( $v ) . '">' . $v . '</li>'; 
                                }
                            }
                            if($tag=='dropdown')    $items[] = '<li data-value="' . esc_attr( $v ) . '" data-search-value="' . esc_attr( $v ) . '">' . $v . '</li>';  
                            if($tag=='checkbox')    $items[] = '<label class="' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input type="checkbox" value="' . esc_attr( $v ) . '" />' . $v . '</label>';
                            if($tag=='radio')       $items[] = '<label class="' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input type="radio" value="' . esc_attr( $v ) . '" />' . $v . '</label>';
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
                        $item = '<li data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr($v->name) . '">';
                        $item .= '<span class="super-wp-tag">' . $v->name . '</span>'; 
                        $item .= '<span class="super-wp-tag-count">×&nbsp;' . $v->count . '</span>'; 
                        if( !empty($v->description) ) {
                            $item .= '<span class="super-wp-tag-desc">' . $v->description . '</span>'; 
                        }
                        $item .= '</li>';
                        $items[] = $item;
                    }else{
                        $items[] = '<li data-value="' . esc_attr($data_value) . '" data-search-value="' . esc_attr( $v->name ) . '">' . $v->name . '</li>';
                    }
                }
                if($tag=='dropdown')    $items[] = '<li data-value="' . esc_attr( $data_value ) . '" data-search-value="' . esc_attr( $v->name ) . '">' . $v->name . '</li>';  
                if($tag=='checkbox')    $items[] = '<label class="' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input type="checkbox" value="' . esc_attr( $data_value ) . '" />' . $v->name . '</label>';
                if($tag=='radio')       $items[] = '<label class="' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input type="radio" value="' . esc_attr( $data_value ) . '" />' . $v->name . '</label>';
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
                            $items[] = '<li ' . ($atts['value']==$value ? 'class="super-active" ' : '') . 'data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $title ) . '">' . $title . '</li>';
                        }else{
                            // text - keywords - csv
                            if($prefix=='keywords_'){
                                $item = '<li data-value="' . esc_attr($value) . '" data-search-value="' . esc_attr($title) . '">';
                                $item .= '<span class="super-wp-tag">' . $title . '</span>'; 
                                $item .= '</li>';
                                $items[] = $item;
                            }else{
                                $items[] = '<li data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $title ) . '">' . $title . '</li>';
                            }
                        }    
                    }
                    if($tag=='dropdown') {
                        // Get advanced tags value
                        $real_value = explode(';', $value)[0];
                        $class = '';
                        if(in_array( $real_value, $selected_values ) ) {
                            $class .= ' super-active';
                            $placeholder[] = $title;
                        }
                        $items[] = '<li ' . (!empty($class) ? 'class="' . $class . '" ' : '' ) . 'data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $title ) . '">' . $title . '</li>';
                    }
                    if($tag=='checkbox') {
                        $items[] = '<label class="' . ( !in_array($value, $selected_values) ? '' : 'super-active') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input' . ( !in_array($value, $selected_items) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $value ) . '" />' . $title . '</label>';
                    }
                    if($tag=='radio') {
                        $items[] = '<label class="' . ( ($atts['value']!=$value) ? '' : 'super-active') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input type="radio" value="' . esc_attr( $value ) . '" />' . $title . '</label>';
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
                                $item = '<li data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $label ) . '">';
                                $item .= '<span class="super-wp-tag">' . $label . '</span>'; 
                                $item .= '</li>';
                                $items[] = $item;
                            }else{
                                $items[] = '<li data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $label ) . '">' . $label . '</li>';
                            }
                        }
                        if($tag=='dropdown')    $items[] = '<li data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $label ) . '">' . $label . '</li>';
                        if($tag=='checkbox')    $items[] = '<label class="' . ( !in_array($value, $selected_items) ? '' : 'super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input' . ( !in_array($value, $selected_items) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $value ) . '" />' . $label . '</label>';
                        if($tag=='radio')       $items[] = '<label class="' . ( ($atts['value']!=$value) ? '' : 'super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input type="radio" value="' . esc_attr( $value ) . '" />' . $label . '</label>';
                        $items_values[] = $value;
                    }
                }
            }
        }


        // dropdown - author
        // checkbox - author
        // radio - author
        // text - autosuggest - author
        // text - keywords - author
        if($atts[$prefix.'retrieve_method']=='users') {
            $exclude_users = '';
            $role_filters = '';
            $default_user_label = '#{ID} - {first_name} {last_name} ({user_email})';
            $meta_keys = 'ID';
            if( !empty( $atts[$prefix.'retrieve_method_exclude_users'] ) ) $exclude_users = $atts[$prefix.'retrieve_method_exclude_users'];
            if( !empty( $atts[$prefix.'retrieve_method_role_filters'] ) ) $role_filters = $atts[$prefix.'retrieve_method_role_filters'];
            if( !empty( $atts[$prefix.'retrieve_method_user_label'] ) ) $default_user_label = $atts[$prefix.'retrieve_method_user_label'];
            if( !empty( $atts[$prefix.'retrieve_method_user_meta_keys'] ) ) $meta_keys = $atts[$prefix.'retrieve_method_user_meta_keys'];
            $args = array(
                'role__in'     => array(),
                'orderby'      => 'login',
                'order'        => 'ASC',
                'exclude'      => array()
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

                        $item = '<li data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $label ) . '">';
                        $item .= '<span class="super-wp-tag">' . $label . '</span>'; 
                        $item .= '</li>';
                        $items[] = $item;
                    }else{
                        $items[] = '<li ' . ($selected || $active ? 'class="super-active" ' : '') . 'data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $label ) . '">' . $label . '</li>';
                    }
                }
                if($tag=='dropdown'){
                    $items[] = '<li ' . ($selected || $active ? 'class="super-active" ' : '') . 'data-value="' . esc_attr( $value ) . '" data-search-value="' . esc_attr( $label ) . '">' . $label . '</li>';
                }
                if($tag=='checkbox'){
                    $items[] = '<label class="' . ( !in_array($value, $selected_items) || $active ? '' : 'super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input' . ( !in_array($value, $selected_items) || $active ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $value ) . '" />' . $label . '</label>';
                }
                if($tag=='radio'){
                    $items[] = '<label class="' . ( $selected || $active ? 'super-active super-default-selected' : '') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input type="radio" value="' . esc_attr( $value ) . '" />' . $label . '</label>';
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
                        $item = '<li data-value="' . esc_attr( $final_value ) . '" data-search-value="' . esc_attr( $final_label ) . '">';
                        $item .= '<span class="super-wp-tag">' . $final_label . '</span>'; 
                        $item .= '</li>';
                        $items[] = $item;
                    }else{
                        $items[] = '<li data-value="' . esc_attr( $final_value ) . '" data-search-value="' . esc_attr( $final_label ) . '">' . $final_label . '</li>';
                    }
                }
                if($tag=='dropdown')    $items[] = '<li data-value="' . esc_attr( $final_value ) . '" data-search-value="' . esc_attr( $final_label ) . '">' . $final_label . '</li>';
                if($tag=='checkbox')    $items[] = '<label class="' . ( !in_array($final_value, $selected_items) ? '' : 'super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input' . ( !in_array($final_value, $selected_items) ? '' : ' checked="checked"') . ' type="checkbox" value="' . esc_attr( $final_value ) . '" />' . $final_label . '</label>';
                if($tag=='radio')       $items[] = '<label class="' . ( ($atts['value']!=$final_value) ? '' : 'super-active super-default-selected') . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '"><input type="radio" value="' . esc_attr( $final_value ) . '" />' . $final_label . '</label>';
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
    public static function output_builder_html( $tag, $group, $data, $inner, $shortcodes=null, $settings=null, $predefined=false, $builder=false ) {


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

        $result = '';
        
        // If we are updating a TAB element, we will send back a json string with all the content
        // Then on the JS side we will generate the HTML
        // Be aware that this is completely a different method/way then from the other elements
        // This is required because TAB element can have different layouts
        if($builder!==false){
            if( empty($data) ) $data = null;
            if( empty($inner) ) $inner = null;
            $i18n = (isset($_POST['i18n']) ? $_POST['i18n'] : '');
            $result .= self::output_element_html( $tag, $group, $data, $inner, $shortcodes, $settings, $i18n, $builder );
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
                    $result .= '<input type="text" value="'.$label.'" />';
                    $result .= '</div>';
                }
                if($tag=='column'){
                    $result .= '<div class="resize super-tooltip" data-content="Change Column Size">';
                        $result .= '<span class="smaller"><i class="fas fa-angle-left"></i></span>';
                        $result .= '<span class="current">' . $data['size'] . '</span><span class="bigger"><i class="fas fa-angle-right"></i></span>';
                    $result .= '</div>';
                }else{
                    $result .= '<div class="super-title">';
                    $result .= $name;
                    if( ($tag!='button') && ($tag!='button') && (isset($data['name'])) ) {
                        $result .= ' <input class="super-tooltip" title="Unique field name" type="text" value="' . esc_attr($data['name']) . '" autocomplete="off" />';
                    }
                    $result .= '</div>';
                }
                $result .= '<div class="super-element-actions">';
                    $result .= '<span class="edit super-tooltip" title="' . esc_html__( 'Edit element', 'super-forms' ) . '"><i class="fas fa-pencil-alt"></i></span>';
                    $result .= '<span class="duplicate super-tooltip" title="' . esc_html__( 'Duplicate element', 'super-forms' ) . '"><i class="fas fa-copy"></i></span>';
                    $result .= '<span class="move super-tooltip" title="' . esc_html__( 'Reposition element', 'super-forms' ) . '"><i class="fas fa-arrows-alt"></i></span>';
                    $result .= '<span class="transfer super-tooltip" title="' . esc_html__( 'Transfer this element (also works across forms)', 'super-forms' ) . '"><i class="fas fa-exchange-alt"></i></span>';
                    $result .= '<span class="transfer-drop super-tooltip" title="' . esc_html__( 'Transfer after this element', 'super-forms' ) . '"><i class="fas fa-arrow-circle-down"></i></span>';
                    $result .= '<span class="minimize super-tooltip" title="' . esc_html__( 'Minimize', 'super-forms' ) . '"><i class="fas fa-minus-square"></i></span>';
                    $result .= '<span class="delete super-tooltip" title="' . esc_html__( 'Delete', 'super-forms' ) . '"><i class="fas fa-times"></i></span>';
                $result .= '</div>';
            $result .= '</div>';
            
            // Check if this is a TAB element
            // TAB elements require a special loop because they have multiple "inner" objects
            if($tag=='tabs'){
                $result .= '<div class="super-element-inner">';
                    if( empty($data) ) $data = null;
                    if( empty($inner) ) $inner = null;
                    $i18n = (isset($_POST['i18n']) ? $_POST['i18n'] : '');
                    $result .= self::output_element_html( $tag, $group, $data, $inner, $shortcodes, $settings, $i18n, true );
                $result .= '</div>';
            }else{
                $result .= '<div class="super-element-inner' . $inner_class . '">';
                    if( ( $tag!='column' ) && ( $tag!='multipart' ) ) {
                        if( empty($data) ) $data = null;
                        if( empty($inner) ) $inner = null;
                        $i18n = (isset($_POST['i18n']) ? $_POST['i18n'] : '');
                        $result .= self::output_element_html( $tag, $group, $data, $inner, $shortcodes, $settings, $i18n, false );
                    }
                    if( !empty( $inner ) ) {
                        foreach( $inner as $k => $v ) {
                            if( empty($v['data'] ) ) $v['data'] = null;
                            if( empty($v['inner'] ) ) $v['inner'] = null;
                            $result .= self::output_builder_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings );
                        }
                    }
                $result .= '</div>';
            }
            $result .= '<textarea name="element-data">' . htmlentities( json_encode( $data ), ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED ) . '</textarea>';
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
            wp_enqueue_style( 'tooltips', SUPER_PLUGIN_FILE.'assets/css/backend/tooltips.css', array(), SUPER_VERSION );    
            wp_enqueue_script( 'tooltips', SUPER_PLUGIN_FILE.'assets/js/backend/tooltips.js', array(), SUPER_VERSION );   
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
        if( $tag=='text' || $tag=='hidden' ) {
            $result .= self::conditional_variable_attributes( $atts );
        }
        if( $tag!='hidden' ) {
            $result .= self::conditional_attributes( $atts );
        }

        // @since 3.2.0 - custom TAB index
        if( (isset($atts['custom_tab_index'])) && ($atts['custom_tab_index']>=0) ) {
            $result .= ' data-super-custom-tab-index="' . absint($atts['custom_tab_index']) . '"';   
        }

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

        $result = '<div' . $style . ' class="super-field-wrapper ' . $wrapper_class . ($atts['icon']!='' ? ' super-icon-' . $atts['icon_position'] . ' super-icon-' . $atts['icon_align'] : '') . '">';
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
            $result .= '<i class="' . $icon_type . ' fa-'.SUPER_Common::fontawesome_bwc($icon_tag).' super-icon"></i>';
        }
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
            wp_enqueue_script( 'iban-check', SUPER_PLUGIN_FILE . 'assets/js/frontend/iban-check.js', array(), SUPER_VERSION );
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
        foreach($data_attributes as $k => $v){
            if($v!=''){
                $result .= ' data-' . $k . '="' . $v . '"';
            }
        }
        
        // @since 4.7.7 - absolute default value based on settings
        if( (!isset($atts['absolute_default'])) && (isset($atts['value'])) ) {
            $atts['absolute_default'] = $atts['value'];
        }
        if( isset($atts['absolute_default']) ) {
            $result .= ' data-absolute-default="' . esc_attr($atts['absolute_default']) . '"';
        }
        
        // @since 2.0.0 - default value data attribute needed for Clear button
        if( isset($atts['value']) ) $result .= ' data-default-value="' . esc_attr($atts['value']) . '"';

        // @since 1.2.2
        if( !empty( $atts['disabled'] ) ) $result .= ' disabled="' . esc_attr($atts['disabled']) . '"';

        // @since 3.6.0
        if( !empty( $atts['readonly'] ) ) $result .= ' readonly="true"';

        // @since 3.6.0 - disable field autocompletion
        if( !empty($atts['autocomplete']) ) $result .= ' autocomplete="off"';

        if( !empty( $atts['default_placeholder'] ) ) {
            $result .= ' placeholder="' . esc_attr($atts['default_placeholder']) . '"';
        }else{
            if( !empty( $atts['placeholder'] ) ) {
                $result .= ' placeholder="' . esc_attr($atts['placeholder']) . '"';
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
            if($tag=='date'){
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
                        wp_enqueue_script( 'masked-input', SUPER_PLUGIN_FILE . 'assets/js/frontend/masked-input.js', array(), SUPER_VERSION );
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

            if( (isset($atts['maxnumber'])) && ($atts['maxnumber']>0) ) {
                $result .= ' data-maxnumber="' . esc_attr($atts['maxnumber']) . '"';
            }
            if( (isset($atts['minnumber'])) && ($atts['minnumber']>0) ) {
                $result .= ' data-minnumber="' . esc_attr($atts['minnumber']) . '"';
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
            $field_names = array();
            foreach( $atts['conditional_items'] as $k => $v ) {
                // @since 3.5.0 - also check if variable field contains tags and if so, update the correct values
                if( !empty($v['field']) ) {
                    $field_names = SUPER_Common::get_data_fields_attribute($field_names, $v['field'], true);
                    $field_names = SUPER_Common::get_data_fields_attribute($field_names, $v['value']);
                }
                if( !empty($v['and_method']) && !empty($v['field_and']) ) {
                    $field_names = SUPER_Common::get_data_fields_attribute($field_names, $v['field_and'], true);
                    $field_names = SUPER_Common::get_data_fields_attribute($field_names, $v['value_and']);
                }
            }
            // @since 1.7 - use json instead of HTML for speed improvements
            return '<textarea class="super-conditional-logic" data-fields="{' . implode('}{', $field_names) . '}">' . json_encode($atts['conditional_items']) . '</textarea>';
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
                    if( (!empty($file)) && (($handle = fopen($file, "r")) !== FALSE) ) {
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
                $field_names = array();
                foreach( $atts['conditional_items'] as $k => $v ) {
                    if( !empty($v['field']) ) {
                        $field_names = SUPER_Common::get_data_fields_attribute($field_names, $v['field'], true);
                        $field_names = SUPER_Common::get_data_fields_attribute($field_names, $v['value']);
                    }
                    if( !empty($v['and_method']) && !empty($v['field_and']) ) {
                        $field_names = SUPER_Common::get_data_fields_attribute($field_names, $v['field_and'], true);
                        $field_names = SUPER_Common::get_data_fields_attribute($field_names, $v['value_and']);
                    }
                    if( !empty($v['new_value']) ) {
                        $field_names = SUPER_Common::get_data_fields_attribute($field_names, $v['new_value']);
                    }
                }
                // @since 1.7 - use json instead of HTML for speed improvements
                return '<textarea class="super-variable-conditions" data-fields="{' . implode('}{', $field_names) . '}">' . json_encode($atts['conditional_items']) . '</textarea>';
            }
        }
    }

    /** 
     * Tabs/Accordion callback function 
     *
     *  @since      4.8.0
    */
    public static function tabs( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'layout_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation
        $result  = '';
        $layout = $atts['layout']; // possible values: tabs, accordion, list
        $result .= '<div class="super-shortcode super-' . $tag . ' super-layout-' . $atts['layout'] . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '">';
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
                            $tab_html .= '<div class="super-tab-image"><img src="' . $image . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                        }
                        $tab_html .= '<div class="super-tab-title">' . $v['title'] . '</div>';
                        $tab_html .= '<div class="super-tab-desc">' . $v['desc'] . '</div>';
                    $tab_html .= '</div>';
                }
                
                // If the only thing that we need to do is update the TABS in the back-end (builder page)
                // Then send a json string back to the JS create-form.js
                // This will then update the TABS html, and also remove or add missing TAB content
                if($builder!==false){
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
                                // First check how many columns there are
                                // This way we can correctly close the column system
                                $GLOBALS['super_column_found'] = 0;
                                foreach( $tab_inner as $iv ) {
                                    if( $iv['tag']=='column' ) $GLOBALS['super_column_found']++;
                                }
                                foreach( $tab_inner as $iv ) {
                                    if( empty($iv['data']) ) $iv['data'] = null;
                                    if( empty($iv['inner']) ) $iv['inner'] = null;
                                    if($builder){
                                        $result .= self::output_builder_html( $iv['tag'], $iv['group'], $iv['data'], $iv['inner'], $shortcodes, $settings );
                                    }else{
                                        $result .= self::output_element_html( $iv['tag'], $iv['group'], $iv['data'], $iv['inner'], $shortcodes, $settings, $i18n, false, $entry_data );
                                    }
                                }
                            }
                            unset($GLOBALS['super_grid_system']);
                            if($builder) $result .= '</div>';
                        $result .= '</div>';
                    $result .= '</div>';
                }
                $result .= '</div>';
                // End of TAB contents
            }
            if($layout=='accordion'){
                // If the only thing that we need to do is update the Accordion header in the back-end (builder page)
                // Then send a json string back to the JS create-form.js
                // This will then update the Accordion header items, 
                if($builder!==false){
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
                        $result .= '<div class="super-accordion-header">';
                            if( !empty( $image ) ) {
                                if( empty( $v['max_width'] ) ) $v['max_width'] = 50;
                                if( empty( $v['max_height'] ) ) $v['max_height'] = 50;
                                $img_styles = '';
                                if( $v['max_width']!='' ) $img_styles .= 'max-width:' . $v['max_width'] . 'px;';
                                if( $v['max_height']!='' ) $img_styles .= 'max-height:' . $v['max_height'] . 'px;';
                                $result .= '<div class="super-accordion-image"><img src="' . $image . '"' . ($img_styles!='' ? ' style="' . $img_styles . '"' : '') . '></div>';
                            }
                            $result .= '<div class="super-accordion-title">' . esc_html($v['title']) . '</div>';
                            $result .= '<div class="super-accordion-desc">' . esc_html($v['desc']) . '</div>';
                        $result .= '</div>';
                        $result .= '<div class="super-accordion-content">';
                            $result .= '<div class="super-padding">';
                                if($builder) $result .= '<div class="super-element-inner super-dropable">';
                                if( !empty($tab_inner) ) {
                                    // First check how many columns there are
                                    // This way we can correctly close the column system
                                    $GLOBALS['super_column_found'] = 0;
                                    foreach( $tab_inner as $iv ) {
                                        if( $iv['tag']=='column' ) $GLOBALS['super_column_found']++;
                                    }
                                    foreach( $tab_inner as $iv ) {
                                        if( empty($iv['data']) ) $iv['data'] = null;
                                        if( empty($iv['inner']) ) $iv['inner'] = null;
                                        if($builder){
                                            $result .= self::output_builder_html( $iv['tag'], $iv['group'], $iv['data'], $iv['inner'], $shortcodes, $settings );
                                        }else{
                                            $result .= self::output_element_html( $iv['tag'], $iv['group'], $iv['data'], $iv['inner'], $shortcodes, $settings, $i18n, false, $entry_data );
                                        }
                                    }
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
    public static function multipart( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {
      
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'layout_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

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
                $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings, $i18n, false, $entry_data );
            }
        }
        unset($GLOBALS['super_grid_system']);
        $result .= '</div>';
        return $result;
    }
    public static function column( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null, $dynamic=0, $dynamic_field_names=array() ) {

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
            if($atts['duplicate']=='enabled' && $entry_data){
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

                $inner_field_names = array();
                foreach($inner as $ik => $iv){
                    if( !empty($iv['data']['name']) ) {
                        $inner_field_names[$iv['data']['name']] = array(
                            'name' => $iv['data']['name'], // Field name
                            'email' => $iv['data']['email'] // Email label
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
                                        if(!empty($v['data']['name'])){
                                            if(isset($inner_field_names[$v['data']['name']])){
                                                $current_name = $v['data']['name'];
                                                if($i>1){
                                                    $v['data']['name'] = $inner_field_names[$current_name]['name'] . '_' . $i;
                                                }else{
                                                    $v['data']['name'] = $inner_field_names[$current_name]['name'];
                                                }
                                                $v['data']['email'] = SUPER_Common::convert_field_email_label($inner_field_names[$current_name]['email'], $i);
                                            }
                                            if( !empty($dv[$current_name]) ) {
                                                if(!empty($dv[$current_name]['value'])) {
                                                    // Now override the "Default value" with the actual Entry data
                                                    $v['data']['value'] = $dv[$current_name]['value'];
                                                }
                                            }
                                        }
                                        $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings, $i18n, false, $entry_data, $i, $dynamic_field_names );
                                    }
                                    $result .= '<div class="super-duplicate-actions">';
                                    $result .= '<span class="super-add-duplicate"></span>';
                                    $result .= '<span class="super-delete-duplicate"></span>';
                                    $result .= '</div>';
                                $result .= '</div>';
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
                                $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings, $i18n, false, $entry_data );
                            }
                            $result .= '<div class="super-duplicate-actions">';
                            $result .= '<span class="super-add-duplicate"></span>';
                            $result .= '<span class="super-delete-duplicate"></span>';
                            $result .= '</div>';
                        $result .= '</div>';
                    }
                }
            }else{
                $grid['level']++;
                $GLOBALS['super_grid_system'] = $grid;
                $GLOBALS['super_column_found'] = 0;
                if( $atts['duplicate']=='enabled' ) {
                    $result .= '<div class="super-shortcode super-duplicate-column-fields">';
                }
                foreach( $inner as $k => $v ) {
                    if( $v['tag']=='column' ) $GLOBALS['super_column_found']++;
                }
                $re = '/\{(.*?)\}/';
                foreach( $inner as $k => $v ) {
                    if( empty($v['data']) ) $v['data'] = null;
                    if( empty($v['inner']) ) $v['inner'] = null;
                    if($dynamic>1){
                        // Rename field name accordingly
                        if(isset($v['data']['name']) && $v['tag']!='button') {
                            $v['data']['name'] = $v['data']['name'].'_'.$dynamic;
                        }
                        // Rename email label
                        if(isset($v['data']['email'])) {
                            $v['data']['email'] = $v['data']['email'].' ('.$dynamic.')';
                        }
                        // Rename conditional logics accordingly
                        if(isset($v['data']['conditional_items'])){
                            foreach($v['data']['conditional_items'] as $ck => $cv){
                                // Only if it exists in field names array
                                if(in_array($cv['field'], $dynamic_field_names)){
                                    $v['data']['conditional_items'][$ck]['field'] = $cv['field'].'_'.$dynamic;
                                }
                                if(in_array($cv['field_and'], $dynamic_field_names)){
                                    $v['data']['conditional_items'][$ck]['field_and'] = $cv['field_and'].'_'.$dynamic;
                                }

                                // Replace {tags}
                                if(isset($cv['value'])){
                                    $str = $cv['value'];
                                    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                                    foreach($matches as $mk => $mv){
                                        // In case advanced tag is used explode it
                                        $values = explode(";", $mv[1]);
                                        if(in_array($values[0], $dynamic_field_names)){
                                            $new_name = $values[0].'_'.$dynamic;
                                            $values[0] = $new_name;
                                            $new_tag = implode(";", $values);
                                            $cv['value'] = str_replace($mv[0], '{'.$new_tag.'}', $cv['value']);
                                        }
                                    }
                                    $v['data']['conditional_items'][$ck]['value'] = $cv['value'];
                                }
                                if(isset($cv['value_and'])){
                                    $str = $cv['value_and'];
                                    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                                    foreach($matches as $mk => $mv){
                                        // In case advanced tag is used explode it
                                        $values = explode(";", $mv[1]);
                                        if(in_array($values[0], $dynamic_field_names)){
                                            $new_name = $values[0].'_'.$dynamic;
                                            $values[0] = $new_name;
                                            $new_tag = implode(";", $values);
                                            $cv['value_and'] = str_replace($mv[0], '{'.$new_tag.'}', $cv['value_and']);
                                        }
                                    }
                                    $v['data']['conditional_items'][$ck]['value_and'] = $cv['value_and'];
                                }
                                if(isset($cv['new_value'])){
                                    $str = $cv['new_value'];
                                    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                                    foreach($matches as $mk => $mv){
                                        // In case advanced tag is used explode it
                                        $values = explode(";", $mv[1]);
                                        if(in_array($values[0], $dynamic_field_names)){
                                            $new_name = $values[0].'_'.$dynamic;
                                            $values[0] = $new_name;
                                            $new_tag = implode(";", $values);
                                            $cv['new_value'] = str_replace($mv[0], '{'.$new_tag.'}', $cv['new_value']);
                                        }
                                    }
                                    $v['data']['conditional_items'][$ck]['new_value'] = $cv['new_value'];
                                }
                            }
                        }
                        // Replace HTML {tags}
                        if($v['tag']=='html') {
                            $str = $v['data']['html'];
                            preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                            foreach($matches as $mk => $mv){
                                // In case advanced tag is used explode it
                                $values = explode(";", $mv[1]);
                                if(in_array($values[0], $dynamic_field_names)){
                                    $new_name = $values[0].'_'.$dynamic;
                                    $values[0] = $new_name;
                                    $new_tag = implode(";", $values);
                                    $v['data']['html'] = str_replace($mv[0], '{'.$new_tag.'}', $v['data']['html']);
                                }
                            }
                        }
                        // Replace calculator math with correct {tags}
                        if(isset($v['data']['math'])){
                            $str = $v['data']['math'];
                            preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                            foreach($matches as $mk => $mv){
                                // In case advanced tag is used explode it
                                $values = explode(";", $mv[1]);
                                if(in_array($values[0], $dynamic_field_names)){
                                    $new_name = $values[0].'_'.$dynamic;
                                    $values[0] = $new_name;
                                    $new_tag = implode(";", $values);
                                    $v['data']['math'] = str_replace($mv[0], '{'.$new_tag.'}', $v['data']['math']);
                                }
                            }
                        }
                    }
                    $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings, $i18n, false, $entry_data, $dynamic, $dynamic_field_names );
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
    public static function quantity_field( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation
        

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

        // @since   4.7.5 - get the value for from entry data
        if( empty($atts['value']) ) $atts['value'] = '0';
        $atts['value'] = self::get_entry_data_value( $tag, $atts['value'], $atts['name'], $entry_data );
        
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
    public static function toggle_field( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

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

        // @since   4.7.5 - get the value for from entry data
        if( ( !isset( $atts['value'] ) ) || ( $atts['value']=='' ) ) $atts['value'] = '0';
        $atts['value'] = self::get_entry_data_value( $tag, $atts['value'], $atts['name'], $entry_data );


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

        $result .= '</div>';
        
        if( !isset($atts['suffix_label']) ) $atts['suffix_label'] = '';
        if( !isset($atts['suffix_tooltip']) ) $atts['suffix_tooltip'] = '';
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
     *  Color picker
     *
     *  @since      3.1.0
    */    
    public static function color( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        wp_enqueue_style( 'super-colorpicker', SUPER_PLUGIN_FILE.'assets/css/frontend/colorpicker.css', array(), SUPER_VERSION );    
        wp_enqueue_script( 'super-colorpicker', SUPER_PLUGIN_FILE . 'assets/js/frontend/colorpicker.js' );

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

        // @since   4.7.5 - get the value for from entry data
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $atts['value'] = self::get_entry_data_value( $tag, $atts['value'], $atts['name'], $entry_data );

        if($atts['value']!='') $atts['value'] = SUPER_Common::email_tags( $atts['value'], null, $settings );

        // @since 3.5.0 - add shortcode compatibility for default field value
        $atts['value'] = do_shortcode($atts['value']); 

        $result .= ' name="' . $atts['name'] . '" value="' . $atts['value'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';
        $result .= '</div>';

        if( !isset( $atts['suffix_label'] ) ) $atts['suffix_label'] = '';
        if( !isset( $atts['suffix_tooltip'] ) ) $atts['suffix_tooltip'] = '';
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
    public static function slider_field( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        wp_enqueue_style( 'simpleslider', SUPER_PLUGIN_FILE.'assets/css/backend/simpleslider.css', array(), SUPER_VERSION );    
        wp_enqueue_script( 'simpleslider', SUPER_PLUGIN_FILE.'assets/js/backend/simpleslider.js', array(), SUPER_VERSION ); 
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        $result .= '<input class="super-shortcode-field" type="text"';
        
        // @since   1.1.8 - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = sanitize_text_field( $_POST[$atts['name']] );
        }

        // @since   4.7.5 - get the value for from entry data
        if( !isset( $atts['value'] ) ) $atts['value'] = '0';
        $atts['value'] = self::get_entry_data_value( $tag, $atts['value'], $atts['name'], $entry_data );

        $result .= ' name="' . $atts['name'] . '" value="' . $atts['value'] . '" data-decimals="' . $atts['decimals'] . '" data-thousand-separator="' . $atts['thousand_separator'] . '" data-decimal-separator="' . $atts['decimal_separator'] . '" data-steps="' . $atts['steps'] . '" data-currency="' . $atts['currency'] . '" data-format="' . $atts['format'] . '" data-minnumber="' . $atts['minnumber'] . '" data-maxnumber="' . $atts['maxnumber'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

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
    public static function currency( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation  

        wp_enqueue_script( 'masked-currency', SUPER_PLUGIN_FILE . 'assets/js/frontend/masked-currency.js', array(), SUPER_VERSION ); 

        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        
        if( !isset( $atts['format'] ) ) $atts['format'] = '';
        if( !isset( $atts['currency'] ) ) $atts['currency'] = '$';
        if( !isset( $atts['decimals'] ) ) $atts['decimals'] = 2;
        if( !isset( $atts['thousand_separator'] ) ) $atts['thousand_separator'] = ',';
        if( !isset( $atts['decimal_separator'] ) ) $atts['decimal_separator'] = '.';

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        $result .= '<input class="super-shortcode-field' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" type="tel"';

        // @since   1.1.8 - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = sanitize_text_field( $_POST[$atts['name']] );
        }

        // @since   4.7.5 - get the value for from entry data
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $atts['value'] = self::get_entry_data_value( $tag, $atts['value'], $atts['name'], $entry_data );

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

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }

    public static function text( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        // @since 4.7.0 - field types
        if( !isset( $atts['type'] ) ) $atts['type'] = 'text';
        if( empty($atts['step']) ) $atts['step'] == 'any';

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
            wp_enqueue_script( 'google-maps-api', '//maps.googleapis.com/maps/api/js?key=' . $atts['address_api_key'] . '&libraries=drawing,geometry,places,visualization&callback=SUPER.google_maps_init', array( 'super-common' ), SUPER_VERSION, false );
        }

        // @since   1.2.4 - auto suggest feature
        if( !isset( $atts['enable_auto_suggest'] ) ) $atts['enable_auto_suggest'] = '';
        $class = ($atts['enable_auto_suggest']=='true' ? 'super-auto-suggest ' : '');

        // @since   4.6.0 - wc order search
        if( !isset( $atts['wc_order_search'] ) ) $atts['wc_order_search'] = '';
        $class .= ($atts['wc_order_search']=='true' ? 'super-wc-order-search ' : '');

        // @since   3.7.0 - auto suggest wp tags
        if( empty($atts['keywords_retrieve_method']) ) $atts['keywords_retrieve_method'] = 'free';
        $class .= ($atts['keywords_retrieve_method']!='free' ? 'super-keyword-tags ' : '');

        // @since   3.1.0 - uppercase transformation
        if( !isset( $atts['uppercase'] ) ) $atts['uppercase'] = '';
        $class .= ($atts['uppercase']=='true' ? ' super-uppercase ' : '');

        $result = self::opening_tag( $tag, $atts, $class );

        // @since   1.1.8 - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = sanitize_text_field( $_POST[$atts['name']] );
        }

        // @since   4.7.5 - get the value for from entry data
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $entry_data_value = self::get_entry_data_value( $tag, $atts['value'], $atts['name'], $entry_data );
        if(!empty($entry_data_value)){
            $atts['value'] = $entry_data_value;
        }

        if($atts['value']!='') $atts['value'] = SUPER_Common::email_tags( $atts['value'], null, $settings );

        // @since 3.5.0 - add shortcode compatibility for default field value
        $atts['value'] = do_shortcode($atts['value']); 


        $wrapper_class = '';
        if( ($atts['enable_auto_suggest']=='true') && (!empty($entry_data[$atts['name']])) && (!empty($entry_data[$atts['name']]['value'])) ) {
            // Check if value exist in one of the items
            // If so add overlap class, otherwise don't and check if user is allowed to enter none-existing values (manual input)
            $get_items = self::get_items(array(), $tag, $atts, '', $settings, $entry_data);
            if(in_array($entry_data[$atts['name']]['value'], $get_items['items_values'])){
                $wrapper_class = 'super-overlap'; 
            }
        }

        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings, $wrapper_class );
        
        // @since 2.9.0 - keyword enabled
        if( !isset( $atts['enable_keywords'] ) ) $atts['enable_keywords'] = '';
        if( !isset( $atts['keyword_split_method'] ) ) $atts['keyword_split_method'] = 'both';
        if( !isset( $atts['keyword_max'] ) ) $atts['keyword_max'] = 5;

        if( $atts['enable_keywords']=='true' ) {
            $result .= '<input class="super-keyword';
        }else{
            $result .= '<input class="super-shortcode-field';
        }
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
        if( $atts['enable_keywords']=='true' ) {
            $result .= ' data-keyword-max="' . $atts['keyword_max'] . '" data-split-method="' . $atts['keyword_split_method'] . '"';
        }
        if( $atts['enable_distance_calculator']=='true' ) {
            $result .= $data_attributes;
        }

        if( $atts['enable_auto_suggest']=='true' ) {
            $get_items = self::get_items(array(), $tag, $atts, '', $settings, $entry_data);
            $items = $get_items['items'];
            $atts = $get_items['atts'];
        }

        $result .= ' name="' . $atts['name'] . '"';
        if( $atts['value']!=='' ) {
            $result .= ' value="' . $atts['value'] . '"';
        }
        if( !empty($entry_data_value) ) {
            $result .= ' data-entry-value="' . $entry_data_value . '"';
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
                $result .= '<div class="super-autosuggest-tags">';
                    $result .= '<div></div>';
                    $result .= '<input class="super-shortcode-field" type="text"';
                    if( !empty( $atts['placeholder'] ) ) {
                        $result .= ' placeholder="' . esc_attr($atts['placeholder']) . '" data-placeholder="' . esc_attr($atts['placeholder']) . '"';
                    }
                    $result .= ' />';
                $result .= '</div>';

                $get_items = self::get_items(array(), $tag, $atts, 'keywords_', $settings, $entry_data);
                $items = $get_items['items'];
                $atts = $get_items['atts'];

                $result .= '<ul class="super-dropdown-ui super-autosuggest-tags-list">';
                $result .= '<li data-value="" data-search-value="" class="super-no-results">' . esc_html__( 'No matches found', 'super-forms' ) . '...</li>';
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
            $result .= '<strong style="color:red;">' . esc_html__( 'Please edit this field and enter your "Google API key" under the "Address auto complete" TAB', 'super-forms' ) . '</strong>';
        }

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= self::loop_variable_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function textarea( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        $result  = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        
        // @since   1.1.8 - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = stripslashes( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = stripslashes( $_POST[$atts['name']] );
        }

        // @since   4.7.5 - get the value for from entry data
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $atts['value'] = self::get_entry_data_value( $tag, $atts['value'], $atts['name'], $entry_data );

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
                            if( !in_array( $k, $wp_styles->queue ) ) $style_content .= wp_remote_fopen("{$includes_url}css/$v.css");
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
                        if( !in_array( $k, $wp_scripts->queue ) ) $result .= "<script type='text/javascript' src='{$includes_url}js/$v.js?$version'></script>";
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
                        if( !in_array( $k, $wp_scripts->queue ) ) $result .= "<script type='text/javascript' src='{$admin_url}js/$v.js?$version'></script>";
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
                $result .= str_replace( 'super-shortcode-field', 'super-shortcode-field super-text-editor super-initialized', $editor_html );
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

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function dropdown( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {
        global $woocommerce;

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

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

        // @since   1.1.8 - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = sanitize_text_field( $_POST[$atts['name']] );
        }

        // @since   4.7.5 - get the value for from entry data
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $atts['value'] = self::get_entry_data_value( $tag, $atts['value'], $atts['name'], $entry_data );
        
        // @since   4.7.7 - make sure we do not lose the default placeholder
        // This is required for dynamic columns
        $atts['default_placeholder'] = $atts['placeholder'];
        $get_items = self::get_items(array(), $tag, $atts, '', $settings, $entry_data);
        $items = $get_items['items'];
        $atts = $get_items['atts'];

        $result .= '<input type="hidden" class="super-shortcode-field';
        $result .= $distance_calculator_class;
        $result .= ($atts['class']!='' ? ' ' . $atts['class'] : '');
        $result .= '"';
        $result .= ($atts['enable_distance_calculator']=='true' ? $data_attributes : '');
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
        if(!empty($get_items['atts']['placeholder'])) {
            $atts['placeholder'] = $get_items['atts']['placeholder'];
        }
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
    public static function checkbox( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        $classes = ' display-' . $atts['display'];
        $result = self::opening_tag( $tag, $atts, $classes );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
        
        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';

        // @since   1.1.8 - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = sanitize_text_field( $_POST[$atts['name']] );
        }

        // @since   4.7.5 - get the value for from entry data
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $atts['value'] = self::get_entry_data_value( $tag, $atts['value'], $atts['name'], $entry_data );

        $get_items = self::get_items(array(), $tag, $atts, '', $settings, $entry_data);
        $items = $get_items['items'];
        $atts = $get_items['atts'];
        $selected_items = explode( ",", $atts['value'] );

        foreach( $items as $v ) {
            $result .= $v;
        }

        $result .= '<input class="super-shortcode-field" type="hidden"';
        $result .= ' name="' . esc_attr( $atts['name'] ) . '" value="' . implode(',',$selected_items) . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function radio( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        $classes = ' display-' . $atts['display'];
        $result = self::opening_tag( $tag, $atts, $classes );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );
     
        // @since   1.1.8 - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = sanitize_text_field( $_POST[$atts['name']] );
        }

        // @since   4.7.5 - get the value for from entry data
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $atts['value'] = self::get_entry_data_value( $tag, $atts['value'], $atts['name'], $entry_data );

        // @since 1.9 - custom class
        if( !isset( $atts['class'] ) ) $atts['class'] = '';     

        $get_items = self::get_items(array(), $tag, $atts, '', $settings, $entry_data);
        $items = $get_items['items'];
        $atts = $get_items['atts'];

        foreach( $items as $v ) {
            $result .= $v;
        }

        $result .= '<input class="super-shortcode-field" type="hidden"';
        $result .= ' name="' . esc_attr( $atts['name'] ) . '" value="' . $atts['value'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function radio_items( $tag, $atts ) {
        return '<label><input ' . ( (($atts['checked']==='false') || ($atts['checked']===false)) ? '' : 'checked="checked"' ) . ' type="radio" value="' . esc_attr( $atts['value'] ) . '" />' . $atts['label'] . '</label>';
    }
    public static function file( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        $dir = SUPER_PLUGIN_FILE . 'assets/js/frontend/jquery-file-upload/';
        wp_enqueue_script( 'jquery-ui-widget' );
        wp_enqueue_script( 'upload-iframe-transport', $dir . 'jquery.iframe-transport.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
        wp_enqueue_script( 'upload-fileupload', $dir . 'jquery.fileupload.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
        wp_enqueue_script( 'upload-fileupload-process', $dir . 'jquery.fileupload-process.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
        wp_enqueue_script( 'upload-fileupload-validate', $dir . 'jquery.fileupload-validate.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
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
        }else{
            $result .= '<i class="fas fa-plus"></i><span class="super-fileupload-button-text">' . $atts['placeholder'] . '</span>';
        }

        $result .= '</div>';
        $atts['placeholder'] = '';
        $result .= '<input class="super-shortcode-field super-fileupload" type="file" name="files[]" data-file-size="' . $atts['filesize'] . '" data-upload-limit="' . $atts['upload_limit'] . '" data-accept-file-types="' . $extensions . '" data-url="' . SUPER_PLUGIN_FILE . 'uploads/php/"';
        if( !isset( $atts['maxlength'] ) ) $atts['maxlength'] = 0;
        if( !isset( $atts['minlength'] ) ) $atts['minlength'] = 0;
        if( ($atts['minlength']>1) || ($atts['maxlength']>1) ) $result .= ' multiple';
        $result .= ' />';
        $result .= '<input class="super-active-files" type="hidden"';
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
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function date( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation
        
        wp_enqueue_script( 'jquery-ui-datepicker', false, array( 'jquery' ), SUPER_VERSION );
        wp_enqueue_script( 'date-format', SUPER_PLUGIN_FILE . 'assets/js/frontend/date-format.js' );
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

        // @since   1.1.8 - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = sanitize_text_field( $_POST[$atts['name']] );
        }

        // @since   4.7.5 - get the value for from entry data
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $atts['value'] = self::get_entry_data_value( $tag, $atts['value'], $atts['name'], $entry_data );

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
        if( isset($atts['excl_days']) && $atts['excl_days']!='' ) {
            $result .= 'data-excl-days="' . $atts['excl_days'] . '"';
        }

        $result .= self::common_attributes( $atts, $tag );
        $result .= ' readonly="true" />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }
    public static function time( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {
        
        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

        wp_enqueue_script( 'jquery-timepicker', SUPER_PLUGIN_FILE . 'assets/js/frontend/timepicker.js' );
        $result = self::opening_tag( $tag, $atts );
        $result .= self::opening_wrapper( $atts, $inner, $shortcodes, $settings );

        // @since   1.1.8 - check if we can find parameters
        if( isset( $_GET[$atts['name']] ) ) {
            $atts['value'] = sanitize_text_field( $_GET[$atts['name']] );
        }elseif( isset( $_POST[$atts['name']] ) ) { // Also check for POST key
            $atts['value'] = sanitize_text_field( $_POST[$atts['name']] );
        }

        // @since   4.7.5 - get the value for from entry data
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $atts['value'] = self::get_entry_data_value( $tag, $atts['value'], $atts['name'], $entry_data );

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

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }    
    public static function rating( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

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

        // @since   4.7.5 - get the value for from entry data
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $atts['value'] = self::get_entry_data_value( $tag, $atts['value'], $atts['name'], $entry_data );

        $i=1;
        while( $i < 6 ) {
            $result .= '<i class="fas fa-star super-rating-star ' . ($i<=(int) $atts['value'] ? 'super-active ' : '') . $atts['class'] . '"></i>';
            $i++;
        }

        $result .= '<input class="super-shortcode-field super-star-rating" type="hidden"';
        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag );
        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

        $result .= '</div>';
        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }

    public static function countries( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

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

        // @since   4.7.5 - get the value for from entry data
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $atts['value'] = self::get_entry_data_value( $tag, $atts['value'], $atts['name'], $entry_data );

        // @since 3.5.0 - add shortcode compatibility for default field value
        $atts['value'] = do_shortcode($atts['value']); 

        $result .= '<input class="super-shortcode-field" type="hidden"';
        $result .= ' value="' . $atts['value'] . '" name="' . $atts['name'] . '"';
        $result .= self::common_attributes( $atts, $tag );

        $result .= ' />';

        // @since 1.2.5     - custom regex validation
        if( !empty($atts['custom_regex']) ) $result .= self::custom_regex( $atts['custom_regex'] );

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
    public static function password( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

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

        $result .= '</div>';
        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
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

        // @since   4.7.5 - get the value for from entry data
        if( !isset( $atts['value'] ) ) $atts['value'] = '';
        $atts['value'] = self::get_entry_data_value( $tag, $atts['value'], $atts['name'], $entry_data );

        // @since   3.0.0 - also allow tags for hidden fields 
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

        if($atts['version']==='v3'){
            $result .= '<div class="super-recaptcha g-recaptcha" data-sitekey="' . $global_settings['form_recaptcha_v3'] . '" data-size="invisible"></div>';
            if( ( $global_settings['form_recaptcha_v3']=='' ) || ( $global_settings['form_recaptcha_v3_secret']=='' ) ) {
                $result .= '<strong style="color:red;">' . esc_html__( 'Please enter your reCAPTCHA key and secret in (Super Forms > Settings > Form Settings)', 'super-forms' ) . '</strong>';
            }
        }else{
            if( empty( $global_settings['form_recaptcha'] ) ) $global_settings['form_recaptcha'] = '';
            if( empty( $global_settings['form_recaptcha_secret'] ) ) $global_settings['form_recaptcha_secret'] = '';
            $result .= '<div class="super-recaptcha' . $atts['align'] . '" data-sitekey="' . $global_settings['form_recaptcha'] . '" data-message="' . $atts['error'] . '"></div>';
            if( ( $global_settings['form_recaptcha']=='' ) || ( $global_settings['form_recaptcha_secret']=='' ) ) {
                $result .= '<strong style="color:red;">' . esc_html__( 'Please enter your reCAPTCHA key and secret in (Super Forms > Settings > Form Settings)', 'super-forms' ) . '</strong>';
            }
        }

        $result .= self::loop_conditions( $atts );
        $result .= '</div>';
        return $result;
    }

    public static function image( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'html_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation
        
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
    public static function heading( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null ) {

        $defaults = SUPER_Common::generate_array_default_element_settings(self::$shortcodes, 'html_elements', $tag);
        $atts = wp_parse_args( $atts, $defaults );
        $atts = self::merge_i18n($atts, $i18n); // @since 4.7.0 - translation

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

    public static function html( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null ) {

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
            
            // @since 2.3.0 - speed improvements for replacing {tags} in HTML fields
            preg_match_all('/{\K[^}]*(?=})/m', $atts['html'], $matches);
            
            // @since 3.8.0 - strip the advanced tags and only return the field name
            $data_fields = array();
            foreach($matches[0] as $k => $v){
                $v = explode(";", $v);
                $data_fields[$v[0]] = $v[0];
            }

            // @since 4.6.0 - also check for foreach loops, and also add those field tags as attribute
            $match = preg_match_all('/foreach\s?\(\s?[\'|"|\s|]?(.*?)[\'|"|\s|]?\)\s?:([\s\S]*?)(?:endforeach\s?;)/', $atts['html'], $matches, PREG_SET_ORDER, 0);
            foreach($matches as $k => $v){
                $original = $v[0];
                $data_fields[$v[1]] = $v[1];
                if( isset( $v[2] ) ) {
                    preg_match_all('/<%\K[^%>]*(?=%>)/m', $v[2], $matches, PREG_SET_ORDER, 0);
                    foreach($matches as $k => $v){
                        $v = explode(";", $v[0]);
                        if($v[0]!=='counter') $data_fields[$v[0]] = $v[0];
                    }
                }
            }

            // @since 4.6.0 - also check for if statements and also add those field tags as attribute
            $match = preg_match_all('/if\s?\(\s?[\'|"|\s|]?(.*?)[\'|"|\s|]?(==|!=|>=|<=|>|<)\s?[\'|"|\s|]?(.*?)[\'|"|\s|]?\)\s?:([\s\S]*?)(?:endif\s?;|(?:elseif\s?:([\s\S]*?))endif\s?;)/', $atts['html'], $matches, PREG_SET_ORDER, 0);
            foreach($matches as $k => $v){
                if( isset( $v[1] ) ) {
                    preg_match_all('/{\K[^}]*(?=})/m', $v[1], $matches, PREG_SET_ORDER, 0);
                    foreach($matches as $k => $v){
                        $v = explode(";", $v[0]);
                        if($v[0]!=='counter') $data_fields[$v[0]] = $v[0];
                    }
                }
                if( isset( $v[3] ) ) {
                    preg_match_all('/{\K[^}]*(?=})/m', $v[3], $matches, PREG_SET_ORDER, 0);
                    foreach($matches as $k => $v){
                        $v = explode(";", $v[0]);
                        if($v[0]!=='counter') $data_fields[$v[0]] = $v[0];
                    }
                }
            }
            $fields = implode('}{', $data_fields);
            $html = $atts['html'];

            if(!is_admin()){
                if( !empty($atts['nl2br']) ) $html = nl2br($html);
                $html_code = do_shortcode(stripslashes($html));
            }else{
                if( !empty($_POST['action']) && $_POST['action']=='super_load_preview' && is_admin()){
                    if( !empty($atts['nl2br']) ) $html = nl2br($html);
                }
                $html_code = '<pre>'.htmlspecialchars(stripslashes($html)).'</pre>';
            }
            $result .= '<div class="super-html-content' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '" data-fields="{' . $fields . '}">' . $html_code . '</div>';
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
            $result .= '<span class="super-back-to-top"' . $i_styles . '><i class="fas fa-chevron-up"></i></span>';
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
        wp_enqueue_script( 'google-maps-api', '//maps.googleapis.com/maps/api/js?key=' . $atts['api_key'] . '&libraries=drawing,geometry,places,visualization&callback=SUPER.google_maps_init', array( 'super-common' ), SUPER_VERSION, false );

        // Add field attributes if {tags} are being used
        $fields = array();

        if( !empty($atts['enable_polyline']) ) {
            $polylines = explode("\n", $atts['polylines']);
            foreach( $polylines as $k => $v ) {
                $coordinates = explode("|", $v);
                if( count($coordinates)<2 ) {
                    $error = esc_html__( 'Incorrect latitude and longitude coordinates for Polylines, please correct and update element!', 'super-forms' );
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
        $fields = implode('}{', $fields);
        $result = '<div class="super-google-map" data-fields="{' . $fields . '}">';
        if( (is_admin()) && (!empty($error)) ) {
            $result .= '<p><strong style="color:red;">' . $error . '</strong></p>';
        }
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
    public static function button( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null ) {

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
            
            $result .= '<div ' . $atts['target'] . 'data-href="' . $url . '" class="super-button-wrap no_link' . ($atts['class']!='' ? ' ' . $atts['class'] : '') . '">';
                $result .= '<div class="super-button-name" data-action="' . $action . '" data-status="' . $atts['entry_status'] . '" data-status-update="' . $atts['entry_status_update'] . '" data-loading="' . $loading . '">';
                    $icon_html = '';
                    if( ( $icon!='' ) && ( $icon_option!='none' ) ) {
                        $icon_html = '<i class="fas fa-' . $icon . '"></i>';
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
        $result .= '</div>';
        return $result;
    }


    /** 
     *  Output the shortcode element on front-end
     *
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
     *
     *  @since      1.0.0
    */
    public static function output_element_html( $tag, $group, $data, $inner, $shortcodes=null, $settings=null, $i18n=null, $builder=false, $entry_data=null, $dynamic=0, $dynamic_field_names=array() ) {
        // @IMPORTANT: before we proceed we must make sure that the "Default value" of a field will still be available
        // Otherwise when a user would duplicate a column that was populated with Entry data this "Default value" would be replaced with the Entry value
        // This is not what we want, because when duplicating a column we would like to reset each element to it's original state (Default value)
        // The below `absolute_default` will be retrieved on the elements attribute called `data-absolute-default=""`
        if(!empty($data['name'])){
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
        return call_user_func( array( $class, $function ), $tag, $data, $inner, $shortcodes, $settings, $i18n, $builder, $entry_data, $dynamic, $dynamic_field_names );
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
            'name' => esc_html__( 'E-mail & Contact Entry Label', 'super-forms' ) . ' *', 
            'label' => esc_html__( 'When using dynamic columns, you can use %d to determine where the counter should be placed e.g: "Product %d quantity:" would be converted into "Product 3 quantity:"', 'super-forms' ),
            'desc' => esc_html__( 'Indicates the field in emails and contact entries. (required)', 'super-forms' ),
            'default' => ( !isset( $attributes['email'] ) ? $default : $attributes['email'] ),
            'required' => true,
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
            'filter_value'=>'taxonomy'
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
    public static function sf_retrieve_method_post_display_layout($value, $parent){
        return array(
            'name' => esc_html__( 'Display Layout', 'super-forms' ),
            'label' => esc_html__( 'Select how the items should be displayed', 'super-forms' ),
            'default' => ( !isset( $value ) ? 'list_vertical' : $value ),
            'type' => 'select', 
            'values' => array(
                'list_vertical' => esc_html__( 'List (vertical)', 'super-forms' ), 
                'list_horizontal' => esc_html__( 'List (horizontal)', 'super-forms' ), 
                'grid' => esc_html__( 'Grid', 'super-forms' ), 
                'slider' => esc_html__( 'Slider', 'super-forms' ), 
            ),
            'filter' => true,
            'parent' => $parent, // retrieve_method
            'filter_value' =>'custom,post_type'
        );
    }
    public static function sf_retrieve_method_post_display_layout_columns($value, $parent){
        return array(
            'name' => esc_html__( 'Number of columns (1 up to 6)', 'super-forms' ),
            'label' => esc_html__( 'Choose how many columns your Grid or Slider will display', 'super-forms' ),
            'default' => ( !isset( $value ) ? 4 : $value ),
            'type' => 'slider', 
            'min' => 1, 
            'max' => 6, 
            'steps' => $steps, 
            'filter' => true,
            'parent' => $parent, // display_layout
            'filter_value' => 'grid,slider'
        );
    }
    public static function sf_retrieve_method_post_display_layout_rows($value, $parent){
        return array(
            'name' => esc_html__( 'Number of rows', 'super-forms' ),
            'label' => esc_html__( 'Choose how many rows your Grid will display', 'super-forms' ),
            'default' => ( !isset( $value ) ? 4 : $value ),
            'type' => 'slider', 
            'min' => 1, 
            'max' => 6, 
            'steps' => 1, 
            'filter' => true,
            'parent' => $parent, // display_layout
            'filter_value' => 'grid,slider'
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
                'parent' => esc_html__( 'Order by post/page parent id', 'super-forms' )
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
        
        // @since 2.1.0 - make sure we reset the grid system
        unset($GLOBALS['super_grid_system']);

        extract( shortcode_atts( array(
            'id' => '',
            'i18n' => ''
        ), $atts ) );

        // @since 4.6.0 - set GET parameters for parsed shortcode params
        foreach($atts as $k => $v){
            // Skip the default "id" parameter
            if($k!='id'){
                // Only save the value if it doesn't exist yet
                // This way it allows us to override any params through URL paremeters
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
        SUPER_Forms()->enqueue_element_scripts($settings);

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

        // Always load the default styles (these can be overwritten by the above loaded style file
        $style_content .= require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/style-default.php' );
      
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
            $entry_data = get_post_meta( $contact_entry_id, '_super_contact_entry_data', true );
            unset($entry_data['hidden_form_id']);
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
                            post_parent IN ($form_ids) AND
                            post_status IN ('publish','super_unread','super_read') AND 
                            post_type = 'super_contact_entry'
                    ORDER BY ID DESC
                    LIMIT 1");
                    if( isset($entry[0])) {
                        $entry_data = get_post_meta( $entry[0]->ID, '_super_contact_entry_data', true );
                        if(!empty($entry_data)){
                            unset($entry_data['hidden_form_id']);
                        }
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

        $result = '';
        if(!$elements_only){
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

            $result .= ' data-field-size="' . $settings['theme_field_size'] . '"';
            
            // @since 4.7.0 - translation
            if(!empty($i18n)){
                $result .= ' data-i18n="' . $i18n . '"';
            }

            $result .= '">';

            // @since 4.7.0 - improved method to center form and to give max width to the form
            if( !empty( $settings['theme_max_width'] ) ) {
                $result .= '<div class="super-max-width-wrapper" style="max-width:' . $settings['theme_max_width'] . 'px;">';
            }

            // @since 4.7.0 - translation langauge switcher
            if(empty($settings['i18n_switch'])) $settings['i18n_switch'] = 'false';
            if($settings['i18n_switch']=='true'){
                $translations = SUPER_Common::get_form_translations($form_id);
                if(!empty($translations) && is_array($translations)){
                    wp_enqueue_style( 'flags', SUPER_PLUGIN_FILE . 'assets/css/frontend/flags.css', array(), SUPER_VERSION );    
                    $default_language = current($translations);
                    // Set default language to current language if not empty
                    if(!empty($i18n)) $default_language = $translations[$i18n];
                    $result .= '<div class="super-i18n-switcher">';
                        $result .= '<div class="super-dropdown">';
                            $result .= '<div class="super-dropdown-placeholder"><img src="'. SUPER_PLUGIN_FILE . 'assets/images/blank.gif" class="flag flag-' . $default_language['flag'] . '" /></div>';
                            $result .= '<ul class="super-dropdown-items">';
                                foreach($translations as $tk => $tv){
                                    $result .= '<li data-value="' . $tk . '"' . ($tv['flag']==$default_language['flag'] ? ' class="super-active"' : '') . '><img src="'. SUPER_PLUGIN_FILE . 'assets/images/blank.gif" class="flag flag-' . $tv['flag'] . '" /></li>';
                                }
                            $result .= '</ul>';
                        $result .= '</div>';
                    $result .= '</div>';
                }
            }

            // @since 1.8 - needed for autocomplete
            $result .= '<form autocomplete="on"';

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
        
        // @since 4.6.0 - add nonce field
        $super_ajax_nonce = wp_create_nonce( 'super_submit_' . $form_id );
        $result .= '<input type="hidden" name="super_ajax_nonce" value="' . $super_ajax_nonce . '" />';

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
        $elements = get_post_meta( $form_id, '_super_elements', true );
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
                $result .= self::output_element_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings, $i18n, false, $entry_data );
            }
        }
        
        // Make sure to only return the default submit button if no custom button was used
        if(!isset($GLOBALS['super_custom_button_used'])){
            $result .= self::button( 'button', array(), '', '', $settings, $i18n );
        }

        // Always unset after all elements have been processed
        unset($GLOBALS['super_custom_button_used']);
        unset($GLOBALS['super_first_multipart']); // @since 2.6.0

        // @since 3.1.0 - filter to add any HTML after the last form element
        $result = apply_filters( 'super_form_after_last_form_element_filter', $result, array( 'id'=>$form_id, 'settings'=>$settings ) );

        if(!$elements_only){
            $result .= '</form>';

            // @since 3.0.0 - new loading method (gif stops/freezes animating when browser is doing javascript at background)
            $result .= '<span class="super-load-icon"></span>';
            
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
            $global_settings = SUPER_Common::get_global_settings();
            if( !empty($global_settings['theme_custom_js']) ) {
                SUPER_Forms()->theme_custom_js = apply_filters( 'super_form_js_filter', $global_settings['theme_custom_js'], array( 'id'=>$form_id, 'settings'=>$settings ) );
            }

            $result = apply_filters( 'super_form_before_do_shortcode_filter', $result, array( 'id'=>$form_id, 'settings'=>$settings ) );
        }
        return do_shortcode( $result );
    }

}

endif;
