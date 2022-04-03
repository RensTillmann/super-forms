<?php
/**
 * Class to handle field types like textarea dropdown etc.
 *
 * @author      feeling4design
 * @category    Admin
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Field_Types
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'SUPER_Field_Types' ) ) :

/**
 * SUPER_Field_Types
 */
class SUPER_Field_Types {
    
    // Loop over fields
    public static function loop_over_fields($fk, $fv){
        $styles = '';
        $filter = '';
        if( isset( $fv['filter'] ) ) $filter = ' super-filter';
        $parent = '';
        $hidden = '';
        if( isset( $fv['parent'] ) ) {
            $parent = 'data-parent="' . $fv['parent'] . '"';
            $hidden = ' super-hidden';
        }
        if( isset( $fv['hidden_setting'] ) ) {
            $styles = 'display:none;';
        }
        if(!empty($fv['_inline_styles'])){
            $styles .= $fv['_inline_styles'];
        }
        if(!empty($styles))  $styles = ' style="' . $styles . '"';

        $filter_value = '';
        if( isset( $fv['filter_value'] ) ) $filter_value = 'data-filtervalue="' . $fv['filter_value'] . '"';
        echo '<div class="super-field super-field-' . $fk . $filter . $hidden . '" ' . $parent . ' ' . $filter_value . $styles . '>';
            echo '<div class="super-field-info">';
                if( (!isset($fv['name'])) && (!isset($fv['desc'])) ) {
                    echo '&nbsp;';
                }else{
                    if( isset( $fv['name'] ) ) {
                        echo '<h2>' . $fv['name'] . '</h2>';
                    }
                    if( isset( $fv['label'] ) ) {
                        echo '<div class="field-description">' . $fv['label'] . '</div>';
                    }
                    if( isset( $fv['desc'] ) ) {
                        echo '<div class="field-description">' . $fv['desc'] . '</div>';
                    }
                }
            echo '</div>';
            if( !isset( $fv['type'] ) ) $fv['type'] = 'text';
            echo '<div class="super-field-fields super-field-type-'.$fv['type'].'">';
                echo call_user_func( array( 'SUPER_Field_Types', $fv['type'] ), $fk, $fv );
                // Loop over children (if this setting has any)
                if(!empty($fv['children'])){
                    foreach( $fv['children'] as $ck => $cv ) {
                        echo self::loop_over_fields($ck, $cv);
                    }
                }
            echo '</div>';

        echo '</div>';
    }

    // @since 4.8.0 - Tab/Accordion Element
    // Tab/Accordion Items
    public static function tab_items( $id, $field, $data ) {
        $translating = filter_input(INPUT_POST, 'translating', FILTER_VALIDATE_BOOLEAN);
        $return = '<div class="field-info-message"></div>';
        // If no data was found make sure to define default values
        if( !isset( $data[$id] ) ) {
            $data[$id] = array(
                array(
                    'image' => '',
                    'title' => esc_html__( 'Tab', 'super-forms' ) . ' 1',
                    'desc' => esc_html__( 'Description', 'super-forms' )
                ),
                array(
                    'image' => '',
                    'title' => esc_html__( 'Tab', 'super-forms' ) . ' 2',
                    'desc' => esc_html__( 'Description', 'super-forms' )
                ),
                array(
                    'image' => '',
                    'title' => esc_html__( 'Tab', 'super-forms' ) . ' 3',
                    'desc' => esc_html__( 'Description', 'super-forms' )
                )
            );
        }

        if( isset( $data[$id] ) ) {
            $return = '';
            foreach( $data[$id] as $k => $v ) {
                if(isset($field['v'][$k])){
                    $v = array_merge($v, $field['v'][$k]);
                }
                $return .= '<div class="super-multi-items super-tab-item">';
                    if($translating!==true){
                        $return .= '<div class="super-sorting">';
                            $return .= '<span class="up"><i class="fas fa-arrow-up"></i></span>';
                            $return .= '<span class="down"><i class="fas fa-arrow-down"></i></span>';
                        $return .= '</div>';
                    }
                    $return .= '<input type="text" placeholder="' . esc_html__( 'Title', 'super-forms' ) . '" value="' . esc_attr( stripslashes($v['title']) ) . '" name="title">';
                    $return .= '<textarea placeholder="' . esc_html__( 'Description', 'super-forms' ) . '" name="desc">' . esc_attr( stripslashes($v['desc']) ) . '</textarea>';
                    if($translating!==true){
                        $return .= '<i class="super-add super-add-item fas fa-plus"></i>';
                        $return .= '<i class="super-delete fas fa-trash-alt"></i>';
                        if( !isset( $v['image'] ) ) $v['image'] = '';
                        $return .= '<div class="image-field browse-images">';
                        $return .= '<span class="button super-insert-image"><i class="far fa-image"></i></span>';
                        $return .= '<ul class="image-preview">';
                        $image = wp_get_attachment_image_src( $v['image'], 'thumbnail' );
                        $image = !empty( $image[0] ) ? $image[0] : '';
                        if( !empty( $image ) ) {
                            $return .= '<li data-file="' . $v['image'] . '">';
                            $return .= '<div class="super-image"><img src="' . esc_url($image) . '"></div>';
                            $return .= '<input type="number" placeholder="' . esc_html__( 'width', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['max_width'] ) ) . '" name="max_width">';
                            $return .= '<span>px</span>';
                            $return .= '<input type="number" placeholder="' . esc_html__( 'height', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['max_height'] ) ) . '" name="max_height">';
                            $return .= '<span>px</span>';
                            $return .= '<a href="#" class="super-delete">'.esc_html__('Delete', 'super-forms').'</a>';
                            $return .= '</li>';
                        }
                        $return .= '</ul>';
                        $return .= '<input type="hidden" name="image" value="' . $v['image'] . '" />';
                        $return .= '</div>';
                    }

                $return .= '</div>';
            }
            $return .= '<textarea name="' . $id . '" class="super-element-field multi-items-json">' . json_encode( stripslashes_deep($data[$id]) ) . '</textarea>';
        }
        return $return;
    }

    // @since 3.8.0 - field to reset submission counter for users
    public static function reset_user_submission_count( $id, $field ) {
        $return  = '<div class="input">';
            $return .= '<span class="super-button super-reset-user-submission-counter super-delete">' . esc_html__( 'Reset Submission Counter for Users', 'super-forms' ) . '</span>';
        $return .= '</div>';
        return $return;
    }

    // @since 3.4.0 - field to reset submission counter
    public static function reset_submission_count( $id, $field ) {
        $return  = '<div class="input">';
            $return .= '<input type="number" id="field-' . $id . '" name="' . $id . '" class="super-element-field" value="' . esc_attr( stripslashes( $field['v'] ) ) . '" />';
            $return .= '<span class="super-button super-reset-submission-counter super-delete">' . esc_html__( 'Reset Submission Counter', 'super-forms' ) . '</span>';
        $return .= '</div>';
        return $return;
    }

    // Previously Created Fields
    public static function previously_created_fields($id, $field){
        $multiple = '';
        $filter = '';
        if(isset($field['multiple'])) $multiple = ' multiple';
        if(isset($field['filter'])) $filter = ' filter';
        $return  = '<div class="input">';
            $return .= '<select id="field-'.$id.'" name="'.$id.'" data-value="'.$field['v'].'" class="super-element-field previously-created-fields '.$multiple.'"'.$multiple.$filter.'>';
            foreach($field['values'] as $k => $v ) {
                $selected = '';
                if($field['v']==$k){
                    $selected = ' selected="selected"';
                }
                $return .= '<option value="'.$k.'"'.$selected.'>'.$v.'</option>';
            }
            $return .= '</select>';
        $return .= '</div>';
        return $return;
    }
    
    // Previously Created Product Fields
    public static function previously_created_product_fields($id, $field){
        $multiple = '';
        $filter = '';
        if(isset($field['multiple'])) $multiple = ' multiple';
        if(isset($field['filter'])) $filter = ' filter';
        $return  = '<div class="input">';
            $return .= '<select id="field-'.$id.'" name="'.$id.'" class="super-element-field previously-created-product-fields '.$multiple.'"'.$multiple.$filter.'>';
            foreach($field['values'] as $k => $v ) {
                $selected = '';
                if($field['v']==$k){
                    $selected = ' selected="selected"';
                }
                $return .= '<option value="'.$k.'"'.$selected.'>'.$v.'</option>';
            }
            $return .= '</select>';
        $return .= '</div>';
        return $return;
    }
   
    // Dropdown Items
    public static function dropdown_items( $id, $field, $data ) {
        $translating = filter_input(INPUT_POST, 'translating', FILTER_VALIDATE_BOOLEAN);
        $return = '<div class="field-info-message"></div>';
        if( !isset( $data[$id] ) ) {
            $data[$id] = array(
                array(
                    'checked' => 'false',
                    'image' => '',
                    'label' => 'First choice',
                    'value' => 'first_choice',
                ),
                array(
                    'checked' => 'false',
                    'image' => '',
                    'label' => 'Second choice',
                    'value' => 'second_choice',
                ),
                array(
                    'checked' => 'false',
                    'image' => '',
                    'label' => 'Third choice',
                    'value' => 'third_choice',
                ),
            );
        }

        if( isset( $data[$id] ) ) {
            $return = '';
            foreach( $data[$id] as $k => $v ) {
                if(isset($field['v'][$k])){
                    $v = array_merge($v, $field['v'][$k]);
                }
                $return .= '<div class="super-multi-items super-dropdown-item">';
                    if( !isset( $v['checked'] ) ) $v['checked'] = 'false';
                    if($translating!==true){
                        $return .= '<input data-prev="'.$v['checked'].'" ' . ($id=='radio_items' || $id=='autosuggest_items' ? 'type="radio"' : 'type="checkbox"') . ( ($v['checked']==1 || $v['checked']=='true') ? ' checked="checked"' : '' ) . '">';
                        $return .= '<div class="super-sorting">';
                            $return .= '<span class="up"><i class="fas fa-arrow-up"></i></span>';
                            $return .= '<span class="down"><i class="fas fa-arrow-down"></i></span>';
                        $return .= '</div>';
                    }
                    $return .= '<input type="text" placeholder="' . esc_html__( 'Label', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['label'] ) ) . '" name="label">';
                    $return .= '<input type="text" ' . ($translating===true ? 'disabled="disabled" ' : '') . 'placeholder="' . esc_html__( 'Value', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['value'] ) ) . '" name="value">';
                    
                    if($translating!==true){
                        $return .= '<i class="super-add super-add-item fas fa-plus"></i>';
                        $return .= '<i class="super-delete fas fa-trash-alt"></i>';

                        // @since v1.2.3
                        if( ($id=='checkbox_items') || ($id=='radio_items') ) {
                            if( !isset( $v['image'] ) ) $v['image'] = '';
                            $return .= '<div class="image-field browse-images">';
                            $return .= '<span class="button super-insert-image"><i class="far fa-image"></i></span>';
                            $return .= '<ul class="image-preview">';
                            $image = wp_get_attachment_image_src( $v['image'], 'thumbnail' );
                            $image = !empty( $image[0] ) ? $image[0] : '';
                            if( !empty( $image ) ) {
                                $return .= '<li data-file="' . $v['image'] . '">';
                                $return .= '<div class="super-image"><img src="' . esc_url($image) . '"></div>';
                                $return .= '<input type="number" placeholder="' . esc_html__( 'width', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['max_width'] ) ) . '" name="max_width">';
                                $return .= '<span>px</span>';
                                $return .= '<input type="number" placeholder="' . esc_html__( 'height', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['max_height'] ) ) . '" name="max_height">';
                                $return .= '<span>px</span>';
                                $return .= '<a href="#" class="super-delete">'.esc_html__('Delete', 'super-forms').'</a>';
                                $return .= '</li>';
                            }
                            $return .= '</ul>';
                            $return .= '<input type="hidden" name="image" value="' . $v['image'] . '" />';
                            $return .= '</div>';
                        }
                    }

                $return .= '</div>';
            }
            $return .= '<textarea name="' . $id . '" class="super-element-field multi-items-json">' . json_encode( stripslashes_deep($data[$id]) ) . '</textarea>';
        }
        return $return;
    }

    // Checkbox Items
    public static function checkbox_items( $id, $field, $data ) {
        return self::dropdown_items( $id, $field, $data );
    }

    // Radio Items
    public static function radio_items( $id, $field, $data ) {
        return self::dropdown_items( $id, $field, $data );
    }

    // Image
    public static function image( $id, $field ) {
        $return  = '<div class="image-field browse-images">';
        $return .= '<span class="button super-insert-image"><i class="fas fa-plus"></i> ' . esc_html__( 'Browse images', 'super-forms' ) . '</span>';
        $return .= '<ul class="image-preview">';
        $image = wp_get_attachment_image_src( $field['v'], 'thumbnail' );
        $image = !empty( $image[0] ) ? $image[0] : '';
        if( !empty( $image ) ) {
            $return .= '<li data-file="' . $field['v'] . '">';
            $return .= '<div class="super-image"><img src="' . esc_url($image) . '"></div>';
            $return .= '<a href="#" class="super-delete">'.esc_html__('Delete', 'super-forms').'</a>';
            $return .= '</li>';
        }
        $return .= '</ul>';
        $return .= '<input type="hidden" name="' . $id . '" value="' . esc_attr( $field['v'] ) . '" id="field-' . $id . '" class="super-element-field" />';
        $return .= '</div>';
        return $return;
    }

    // File
    // @since   1.0.6
    public static function file( $id, $field ) {
        if(!isset($field['file_type'])) $field['file_type'] = '';
        if(!isset($field['multiple'])) $field['multiple'] = 'false';
        $return  = '<div class="image-field browse-files" data-file-type="' . $field['file_type'] . '" data-multiple="' . $field['multiple'] . '">';
        $return .= '<span class="button super-insert-files"><i class="fas fa-plus"></i> ' . esc_html__( 'Browse files', 'super-forms' ) . '</span>';
        $return .= '<ul class="file-preview">';
        $files = explode(',', $field['v']);
        foreach($files as $k => $v){
            $file = get_attached_file($v);
            if( $file ) {
                $url = wp_get_attachment_url($v);
                $filename = basename ( $file );
                $base = includes_url() . "/images/media/";
                $type = get_post_mime_type($v);
                switch ($type) {
                    case 'image/jpeg':
                    case 'image/png':
                    case 'image/gif':
                      $icon = $url; break;
                    case 'video/mpeg':
                    case 'video/mp4': 
                    case 'video/quicktime':
                      $icon = $base . "video.png"; break;
                    case 'text/csv':
                    case 'text/plain': 
                    case 'text/xml':
                      $icon = $base . "text.png"; break;
                    default:
                      $icon = $base . "document.png";
                }

                $return .= '<li data-file="' . $v . '">';
                $return .= '<div class="super-image"><img src="' . esc_url($icon) . '"></div>';
                $return .= '<a href="' . esc_url($url) . '">' . $filename . '</a>';
                $return .= '<a href="#" class="super-delete">'.esc_html__('Delete', 'super-forms').'</a>';
                $return .= '</li>';
            }
        }
        $return .= '</ul>';
        $return .= '<input type="hidden" name="' . $id . '" value="' . esc_attr( $field['v'] ) . '" id="field-' . $id . '" class="super-element-field" />';
        $return .= '</div>';
        return $return;
    }

    //TinyMCE
    public static function tiny_mce($id, $field){
        $return = '';
        $return .= '<div class="super-element super-shortcode-tinymce super-shortcode-target-content">';
        $content = stripslashes($field['v']);
        ob_start();
        wp_editor( $content, $id , array('editor_class' => 'super-advanced-textarea super-tinymce', 'media_buttons' => true ) );
        $return .= ob_get_clean();
        $return .= '</div>';
        return $return;
    }
    
    //Number slider
    public static function slider($id, $field){
        $return  = '<div class="slider-field">';
        $return .= '<input type="text" name="'.$id.'" value="'.esc_attr($field['v']).'" id="field-'.$id.'" data-steps="'.$field['steps'].'" data-min="'.$field['min'].'" data-max="'.$field['max'].'" class="super-element-field" />';
        $return .= '</div>';
        return $return;
    }
    
    //Input field    
    public static function text( $id, $field ) {
        $return  = '<div class="input">';
            $return .= '<input'.(isset($field['lockToGlobalSetting']) && $field['lockToGlobalSetting']===true ? ' disabled' : '').' type="text" id="field-' . $id . '"';
            if( isset( $field['placeholder'] ) ) {
                $return .= ( $field['placeholder']!='' ? 'placeholder="' . $field['placeholder'] . '"' : '' );
            }
            if( isset( $field['required'] ) ) {
                $return .= ( $field['required']==true ? 'required="true"' : '');
            }
            if( isset( $field['maxlength'] ) ) {
                $return .= ( $field['maxlength'] > 0 ? 'maxlength="' . $field['maxlength'] . '"' : '' );
            }
            $return .= 'name="' . $id . '" class="super-element-field" value="' . esc_attr( stripslashes( $field['v'] ) ) . '" />';
        $return .= SUPER_Common::reset_setting_icons($field);
        $return .= '</div>';
        if( isset( $field['info'] ) ) $return .= '<p>' . $field['info'] . '</p>';
        return $return;
    }

    // @since 4.0.0  - conditional check field (2 fields next to eachother)
    public static function conditional_check( $id, $field ) {
        $return  = '<div class="super-conditional-check">';
            $defaults = explode(',', $field['v']);
            if(!isset($defaults[0])) $defaults[0] = '';
            if(!isset($defaults[1])) $defaults[1] = '==';
            if(!isset($defaults[2])) $defaults[2] = '';

            $placeholders = array();
            if( isset( $field['placeholder'] ) ) {
                $placeholders = explode(',', $field['placeholder']);
            }
            if(!isset($placeholders[0])) $placeholders[0] = '';
            if(!isset($placeholders[1])) $placeholders[1] = '';

            $return .= '<input type="text"';
            // get first part of placeholder
            $return .= ( $placeholders[0]!='' ? 'placeholder="' . $placeholders[0] . '"' : '' );
            $return .= 'name="' . $id . '_1" class="super-element-field" value="' . esc_attr( stripslashes( $defaults[0] ) ) . '" />';

            $return .= '<select name="' . $id . '_2">';
            $return .= '<option' . ($defaults[1]=='==' ? ' selected="selected"' : '') . ' value="==">== (' . esc_html__( 'Equal', 'super-forms' ) . '</option>';
            $return .= '<option' . ($defaults[1]=='!=' ? ' selected="selected"' : '') . ' value="!=">!= (' . esc_html__( 'Not equals', 'super-forms' ) . ')</option>';

            $return .= '<input type="text"';
            // get second part of placeholder
            $return .= ( $placeholders[1]!='' ? 'placeholder="' . $placeholders[1] . '"' : '' );
            $return .= 'name="' . $id . '_3" class="super-element-field" value="' . esc_attr( stripslashes( $defaults[2] ) ) . '" />';

            $return .= '<input type="hidden" name="' . $id . '" class="super-element-field" value="' . esc_attr( stripslashes( $field['v'] ) ) . '" />';
        $return .= '</div>';
        return $return;
    }


    //Checkbox field
    public static function checkbox( $id, $field ) {
        $return = '';
        $return .= '<div class="input">';
            $return .= '<div class="super-checkbox">';
            foreach( $field['values'] as $k => $v ) {
                $return .= '<label>';
                $return .= '<input'.(isset($field['lockToGlobalSetting']) && $field['lockToGlobalSetting']===true ? ' disabled' : '').' type="checkbox" value="' . $k . '" ' . (isset($field['v']) && $field['v']==$k ? 'checked="checked"' : '') . '>';
                $return .= $v;
                if( isset( $field['desc'] ) ) $return .= '<i class="info super-tooltip" title="' . esc_attr($field['desc']) . '"></i>';
                $return .= '</label>';
            }
            $return .= '</div>';
            if(!isset($field['v'])) $field['v'] = '';
            $return .= '<input type="hidden" name="' . $id . '" value="' . esc_attr( $field['v'] ) . '" id="field-' . $id . '" class="super-element-field" />';
        $return .= SUPER_Common::reset_setting_icons($field);
        $return .= '</div>';
        return $return;
    }

    //Input field    
    public static function password($id, $field){
        $return  = '<div class="input">';
            $return .= '<input type="password" id="field-'.$id.'"';
            if(isset($field['placeholder'])){
                $return .= ($field['placeholder']!='' ? 'placeholder="'.$field['placeholder'].'"' : '');
            }
            if(isset($field['required'])){
                $return .= ($field['required']==true ? 'required="true"' : '');
            }
            if(isset($field['maxlength'])){
                $return .= ($field['maxlength'] > 0 ? 'maxlength="' . $field['maxlength'] . '"' : '');
            }
            $return .= 'name="'.$id.'" class="super-element-field" value="' . esc_attr( $field['v'] ) . '" />';
        $return .= '</div>';
        return $return;
    }

    //Textarea  
    public static function textarea( $id, $field ) {
        $field = wp_parse_args( $field, array(
            'rows'    => 3,
            'default' => ''
        ) );
        $return  = '<div class="input">';
            $return .= '<textarea'.(isset($field['lockToGlobalSetting']) && $field['lockToGlobalSetting']===true ? ' disabled' : '').' name="' . $id . '" ';
            if(isset($field['placeholder'])){
                $return .= ($field['placeholder']!='' ? 'placeholder="'.$field['placeholder'].'"' : '');
            }        
            if(isset($field['required'])){
                $return .= ($field['required']==true ? 'required="true" ' : '');
            }
            $value = esc_textarea(wp_unslash($field['v']));
            $return .= 'rows="' . $field['rows'] . '" class="super-element-field">' . $value . '</textarea>';
        $return .= SUPER_Common::reset_setting_icons($field);
        $return .= '</div>';
        return $return;
    }
    public static function tinymce( $id, $field ) {
        $field = wp_parse_args( $field, array(
            'rows'    => 3,
            'default' => ''
        ) );
        $return = '<textarea name="' . $id . '" id="super-tinymce-instance-' . $id . '" ';
        if(isset($field['placeholder'])){
            $return .= ($field['placeholder']!='' ? 'placeholder="'.$field['placeholder'].'"' : '');
        }        
        if(isset($field['required'])){
            $return .= ($field['required']==true ? 'required="true" ' : '');
        }
        $value = esc_textarea(wp_unslash($field['v']));
        $return .= 'rows="' . $field['rows'] . '" class="super-element-field super-textarea-tinymce">' . $value . '</textarea>';
        return $return;
    }
    
    // address_auto_complete
    public static function address_auto_populate( $id, $field, $data ) {
        $mappings = array(
            'name' => esc_html__( 'Name of place', 'super-forms' ),
            'formatted_address' => esc_html__( 'The Place\'s full address', 'super-forms' ),
            'formatted_phone_number' => esc_html__( 'The Place\'s phone number, formatted according to the number\'s regional convention', 'super-forms' ),
            'international_phone_number' => esc_html__( 'The Place\'s phone number in international format', 'super-forms' ),
            'website' => esc_html__( 'The authoritative website for this Place, such as a business\' homepage', 'super-forms' ),
            'street_number' => esc_html__( 'Street number', 'super-forms' ),
            'street_name' => esc_html__( 'Street name', 'super-forms' ),
            'street_name_number' => esc_html__( 'Street name + nr', 'super-forms' ),
            'street_number_name' => esc_html__( 'Street nr + name', 'super-forms' ),
            'city' => esc_html__( 'City name', 'super-forms' ), // see: https://developers.google.com/maps/documentation/javascript/examples/places-autocomplete-addressform
            'state' => esc_html__( 'State/Province', 'super-forms' ),
            'postal_code' => esc_html__( 'Postal code', 'super-forms' ),
            'country' => esc_html__( 'Country name', 'super-forms' ),
            'municipality' => esc_html__( 'Municipality', 'super-forms' ),
            'lat' => esc_html__( 'Latitude', 'super-forms' ),
            'lng' => esc_html__( 'Longitude', 'super-forms' )
        );
        $return = '';
        if( ( isset( $data[$id] ) ) && ( $data[$id]!='' ) ) {
            $i = 0;
            foreach( $mappings as $k => $v ) {
                $return .= '<div class="super-multi-items address-auto-popuplate-item">';
                    $return .= '<strong>' . $v . '</strong>';
                    $return .= '<input type="hidden" name="key" value="' . $k . '" />';
                    $return .= '<select class="super-previously-created" name="field" data-value="' . $data[$id][$i]['field'] . '"></select>';
                    $type = $data[$id][$i]['type'];
                    $return .= '<select name="type" data-value="' . $type . '">';
                        $return .= '<option value="">- '.esc_html__('retrieve method', 'super-forms').' -</option>';
                        $return .= '<option value="long"' . ($type=='long' ? ' selected="selected"' : '') . '>'.esc_html__('Long name (default)', 'super-forms').'</option>';
                        $return .= '<option value="short"' . ($type=='short' ? ' selected="selected"' : '') . '>'.esc_html__('Short name', 'super-forms').'</option>';
                    $return .= '</select>';
                $return .= '</div>';
                $i++;
            }
        }else{
            foreach( $mappings as $k => $v ) {
                $return .= '<div class="super-multi-items address-auto-popuplate-item">';
                    $return .= '<strong>' . $v . '</strong>';
                    $return .= '<input type="hidden" name="key" value="' . $k . '" />';
                    $return .= '<select class="super-previously-created" name="field" data-value=""></select>';
                    $return .= '<select name="type" data-value="">';
                        $return .= '<option value="">- '.esc_html__('retrieve method', 'super-forms').' -</option>';
                        $return .= '<option value="long">'.esc_html__('Long name (default)', 'super-forms').'</option>';
                        $return .= '<option value="short">'.esc_html__('Short name', 'super-forms').'</option>';
                    $return .= '</select>';
                $return .= '</div>';
            }
        }

        if( is_array( $field['v'] ) ) {
            $field['v'] = json_encode( stripslashes_deep($field['v']) );
        } 
        $return .= '<textarea name="' . $id . '" class="super-element-field multi-items-json">' . $field['v'] . '</textarea>';
        return $return;
    }

    //Conditions
    public static function conditions( $id, $field, $data ) {
        $options = array(
            'contains' => '?? Contains',
            'not_contains' => '!! Not contains',
            'equal' => '== Equal',
            'not_equal' =>'!= Not equal',
            'greater_than' => '> Greater than',
            'less_than' => '<  Less than',
            'greater_than_or_equal' => '>= Greater than or equal to',
            'less_than_or_equal' => '<= Less than or equal',            
        );
        if( ( isset( $data[$id] ) ) && ( $data[$id]!='' ) ) {
            $return = '';
            foreach( $data[$id] as $k => $v ) {
                if( !isset( $v['and_method'] ) ) $v['and_method'] = '';
                if( !isset( $v['field_and'] ) ) $v['field_and'] = '';
                if( !isset( $v['logic_and'] ) ) $v['logic_and'] = '';
                if( !isset( $v['value_and'] ) ) $v['value_and'] = '';
                $field_value = (strpos($v['field'], '{')!==false ? $v['field'] : ($v['field']!=='' ? '{'.$v['field'].'}' : ''));
                $field_and = (strpos($v['field_and'], '{')!==false ? $v['field_and'] : ($v['field_and']!=='' ? '{'.$v['field_and'].'}' : ''));
                $return .= '<div class="super-multi-items super-conditional-item">';
                    $return .= '<input type="text" placeholder="Field {tag}" value="' . $field_value . '" name="conditional_field">';
                    $return .= '<select name="conditional_logic">';
                        $return .= '<option selected="selected" value="">---</option>';
                        foreach( $options as $ok => $ov ) {
                            $return .= '<option' . ($ok==$v['logic'] ? ' selected="selected"' : '') . ' value="' . $ok . '">' . $ov . '</option>';
                        }
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Value" value="' . $v['value'] . '" name="conditional_value">';
                    $return .= '<select name="conditional_and_method">';
                        $return .= '<option selected="selected" value="">- select -</option>';
                        $return .= '<option' . ('and'==$v['and_method'] ? ' selected="selected"' : '') . '  value="and">'.esc_html__('AND', 'super-forms').'</option>';
                        $return .= '<option' . ('or'==$v['and_method'] ? ' selected="selected"' : '') . '  value="or">'.esc_html__('OR', 'super-forms').'</option>';
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Field {tag}" value="' . $field_and . '" name="conditional_field_and">';
                    $return .= '<select name="conditional_logic_and">';
                        $return .= '<option selected="selected" value="">---</option>';
                        foreach( $options as $ok => $ov ) {
                            $return .= '<option' . ($ok==$v['logic_and'] ? ' selected="selected"' : '') . ' value="' . $ok . '">' . $ov . '</option>';
                        }
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Value" value="' . $v['value_and'] . '" name="conditional_value_and">';
                    $return .= '<i class="super-add fas fa-plus"></i>';
                    $return .= '<i class="super-delete fas fa-trash-alt" style="visibility: hidden;"></i>';
                    $return .= '<span class="line-break"></span>';
                $return .= '</div>';
            }
        }else{
            $return  = '<div class="super-multi-items super-conditional-item">';
                $return .= '<input type="text" placeholder="Field {tag}" value="" name="conditional_field">';
                $return .= '<select name="conditional_logic">';
                    $return .= '<option selected="selected" value="">---</option>';
                    foreach( $options as $ok => $ov ) {
                        $return .= '<option value="' . $ok . '">' . $ov . '</option>';
                    }
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Value" value="" name="conditional_value">';
                $return .= '<select name="conditional_and_method">';
                    $return .= '<option selected="selected" value="">- select -</option>';
                    $return .= '<option value="and">'.esc_html__('AND', 'super-forms').'</option>';
                    $return .= '<option value="or">'.esc_html__('OR', 'super-forms').'</option>';
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Field {tag}" value="" name="conditional_field_and">';
                $return .= '<select name="conditional_logic_and">';
                    $return .= '<option selected="selected" value="">---</option>';
                    foreach( $options as $ok => $ov ) {
                        $return .= '<option value="' . $ok . '">' . $ov . '</option>';
                    }
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Value" value="" name="conditional_value_and">';
                $return .= '<i class="super-add fas fa-plus"></i>';
                $return .= '<i class="super-delete fas fa-trash-alt" style="visibility: hidden;"></i>';
                $return .= '<span class="line-break"></span>';
            $return .= '</div>';
        }
        if( is_array( $field['v'] ) ) {
            $field['v'] = json_encode( stripslashes_deep($field['v']) );
        }
        $return .= '<textarea name="' . $id . '" class="super-element-field multi-items-json">' . $field['v'] . '</textarea>';
        return $return;
    }

    // @since 1.2.7 Variable Conditions
    public static function variable_conditions( $id, $field, $data ) {
        
        $options = array(
            'contains' => '?? Contains',
            'not_contains' => '!! Not contains',
            'equal' => '== Equal',
            'not_equal' => '!= Not equal',
            'greater_than' => '> Greater than',
            'less_than' => '<  Less than',
            'greater_than_or_equal' => '>= Greater than or equal to',
            'less_than_or_equal' => '<= Less than or equal',            
        );

        // Backward compatability
        $variable_conditions = array();
        if($id==='conditional_variable_items'){
            // Check if it exists, if so use it
            if( !empty( $data[$id] ) ) {
                $variable_conditions = $data[$id];
            }else{
                // Try to get the old conditions
                if( ( isset( $data['conditional_items'] ) ) && ( $data['conditional_items']!='' ) ) {
                    $variable_conditions = $data['conditional_items'];
                }
            }
        }
        if( !empty($variable_conditions) ) {
            $return = '';
            foreach( $variable_conditions as $v ) {
                if( !isset( $v['and_method'] ) ) $v['and_method'] = '';
                if( !isset( $v['field_and'] ) ) $v['field_and'] = '';
                if( !isset( $v['logic_and'] ) ) $v['logic_and'] = '';
                if( !isset( $v['value_and'] ) ) $v['value_and'] = '';
                $field_value = (strpos($v['field'], '{')!==false ? $v['field'] : ($v['field']!=='' ? '{'.$v['field'].'}' : ''));
                $field_and = (strpos($v['field_and'], '{')!==false ? $v['field_and'] : ($v['field_and']!=='' ? '{'.$v['field_and'].'}' : ''));
                $return .= '<div class="super-multi-items super-conditional-item">';
                    $return .= '<input type="text" placeholder="Field {tag}" value="' . $field_value . '" name="conditional_field">';
                    $return .= '<select name="conditional_logic">';
                        $return .= '<option selected="selected" value="">---</option>';
                        foreach( $options as $ok => $ov ) {
                            $return .= '<option' . ($ok==$v['logic'] ? ' selected="selected"' : '') . ' value="' . $ok . '">' . $ov . '</option>';
                        }
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Value" value="' . $v['value'] . '" name="conditional_value">';
                    $return .= '<select name="conditional_and_method">';
                        $return .= '<option selected="selected" value="">- select -</option>';
                        $return .= '<option' . ('and'==$v['and_method'] ? ' selected="selected"' : '') . '  value="and">'.esc_html__('AND', 'super-forms').'</option>';
                        $return .= '<option' . ('or'==$v['and_method'] ? ' selected="selected"' : '') . '  value="or">'.esc_html__('OR', 'super-forms').'</option>';
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Field {tag}" value="' . $field_and . '" name="conditional_field_and">';
                    $return .= '<select name="conditional_logic_and">';
                        $return .= '<option selected="selected" value="">---</option>';
                        foreach( $options as $ok => $ov ) {
                            $return .= '<option' . ($ok==$v['logic_and'] ? ' selected="selected"' : '') . ' value="' . $ok . '">' . $ov . '</option>';
                        }
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Value" value="' . $v['value_and'] . '" name="conditional_value_and">';
                    $return .= '<i class="super-add fas fa-plus"></i>';
                    $return .= '<i class="super-delete fas fa-trash-alt" style="visibility: hidden;"></i>';
                    $return .= '<span class="line-break"></span>';
                    $return .= '<p>' . esc_html__( 'When above conditions are met set following value:', 'super-forms' ) . '</p>';
                    $return .= '<textarea placeholder="New value" name="conditional_new_value">' . ( isset($v['new_value']) ? stripslashes( $v['new_value'] ) : '' ) . '</textarea>';
                    $return .= '</div>';
            }
        }else{
            $return  = '<div class="super-multi-items super-conditional-item">';
                $return .= '<input type="text" placeholder="Field {tag}" value="" name="conditional_field">';
                $return .= '<select name="conditional_logic">';
                    $return .= '<option selected="selected" value="">---</option>';
                    foreach( $options as $ok => $ov ) {
                        $return .= '<option value="' . $ok . '">' . $ov . '</option>';
                    }
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Value" value="" name="conditional_value">';
                $return .= '<select name="conditional_and_method">';
                    $return .= '<option selected="selected" value="">- select -</option>';
                    $return .= '<option value="and">'.esc_html__('AND', 'super-forms').'</option>';
                    $return .= '<option value="or">'.esc_html__('OR', 'super-forms').'</option>';
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Field {tag}" value="" name="conditional_field_and">';
                $return .= '<select name="conditional_logic_and">';
                    $return .= '<option selected="selected" value="">---</option>';
                    foreach( $options as $ok => $ov ) {
                        $return .= '<option value="' . $ok . '">' . $ov . '</option>';
                    }
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Value" value="" name="conditional_value_and">';
                $return .= '<i class="super-add fas fa-plus"></i>';
                $return .= '<i class="super-delete fas fa-trash-alt" style="visibility: hidden;"></i>';
                $return .= '<span class="line-break"></span>';
                $return .= '<p>' . esc_html__( 'When above conditions are met set following value:', 'super-forms' ) . '</p>';
                $return .= '<textarea placeholder="New value" value="" name="conditional_new_value"></textarea>';
                $return .= '</div>';
        }
        if( is_array( $field['v'] ) ) {
            $field['v'] = json_encode( stripslashes_deep($field['v']) );
        }
        $return .= '<textarea name="' . $id . '" class="super-element-field multi-items-json">' . $field['v'] . '</textarea>';
        return $return;
    }
    

 
    //Time field    
    public static function time($id, $field){
        $return  = '<div class="input">';
            $return .= '<input type="text" id="field-'.$id.'"';
            if(isset($field['required'])){
                $return .= ($field['required']==true ? 'required="true"' : '');
            }
            $return .= 'name="'.$id.'" data-format="H:i" data-step="5" class="super-element-field super-timepicker" value="'.esc_attr($field['v']).'" />';
        $return .= '</div>';
        return $return;
    }
    
    // @since 4.8.0 - Image select field
    public static function image_select($id, $field){
        $multiple = '';
        $filter = '';
        if( isset( $field['multiple'] ) ) $multiple = ' multiple';
        if( isset( $field['filter'] ) ) $filter = ' filter';
        $return  = '<div class="input">';
            $return .= '<div class="super-image-select">';
            foreach( $field['values'] as $k => $v ) {
                $selected = '';
                $class = '';
                if( isset($field['v']) && $field['v']==$k ) {
                    $selected = ' checked="checked"';
                    $class = ' super-active';
                }
                $return .= '<div class="super-image-select-option' . $class . '">';
                    $return .= '<input type="radio"' . $selected . ' value="' . esc_attr( $k ) . '" id="field-' . $id . '" name="' . $id . '" class="super-element-field"' . $filter . '>';
                    $return .= '<span class="super-image-select-option-icon"><i class="' . $v['icon'] . '"></i></span>';
                    $return .= '<span class="super-image-select-option-title">' . esc_html( $v['title'] ) . '</span>';
                $return .= '</div>';
            }
            $return .= '</div>';
            if( isset( $field['info'] ) ) $return .= '<p>' . $field['info'] . '</p>';
        $return .= '</div>';
        return $return;
    }

    //Dropdown - Select field
    public static function select($id, $field){
        $multiple = '';
        $filter = '';
        if( isset( $field['multiple'] ) ) $multiple = ' multiple';
        if( isset( $field['filter'] ) ) $filter = ' filter';
        $return  = '<div class="input">'; 
            $return .= '<select'.(isset($field['lockToGlobalSetting']) && $field['lockToGlobalSetting']===true ? ' disabled' : '').' id="field-' . $id . '" name="' . $id . '" class="super-element-field ' . $multiple . '"' . $multiple . $filter . '>';
            foreach( $field['values'] as $k => $v ) {
                $selected = '';
                if( ( isset( $field['multiple'] ) ) && ( $field['v']!='' ) ) {
                    if( in_array( $k, $field['v'] ) ) {
                        $selected = ' selected="selected"';
                    }
                }else{
                    if( isset($field['v']) && $field['v']==$k ) {
                        $selected = ' selected="selected"';
                    }
                }
                $return .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
            }
            $return .= '</select>';
            if( isset( $field['info'] ) ) $return .= '<p>' . $field['info'] . '</p>';

        $return .= SUPER_Common::reset_setting_icons($field);
        $return .= '</div>';
        return $return;
    }
    
    //Color picker
    public static function color($id, $field){
        $return  = '<div class="super-color-picker-container">';
            $return .= '<div class="super-color-picker">';
                $return .= '<input type="text" id="field-'.$id.'" name="'.$id.'" class="super-element-field" value="'.esc_attr($field['v']).'" />';
            $return .= '</div>';
        $return .= '</div>';
        return $return;
    }
    
    //Multi Color picker
    public static function multicolor($id, $field){
        $return = '<div class="input super-field-type-'.$field['type'].'">';
        foreach($field['colors'] as $k => $v){
            $return .= '<div class="super-color-picker-container';
            // If locked to global value `_g_` then add class
            $v['lockToGlobalSetting'] = false;
            if($v['v']==='_g_'){
                $v['v'] = $v['g'];
                $v['lockToGlobalSetting'] = true;
                $return .= ' _g_';
            }
            $return .= '">';
                if(isset($v['label'])) $return .= '<div class="super-color-picker-label">'.$v['label'].'</div>';
                $return .= '<div class="super-color-picker">';
                    $return .= SUPER_Common::reset_setting_icons($v);
                    $return .= '<input type="text" id="field-'.$k.'" name="'.$k.'" class="super-element-field" value="'.esc_attr($v['v']).'" />';
                $return .= '</div>';
            $return .= '</div>';
        }
        $return .= '</div>';
        return $return;
    }
    
    //Icon list
    public static function icon($id, $field){
        
        // Default value for v5+
        // circle;fas
        // circle;far

        // Deafult value for v4
        // circle

        $return  = '<div class="super-icon-field">';
        $icons = self::icons();
        $return .= '<div class="super-icon-search"><input type="text" placeholder="Filter icons" /></div>';
        $return .= '<div class="super-icon-list">';

        $default = explode(';', $field['v']);
        $type = 'fas';
        if(isset($default[1])){
            $type = $default[1]; // use the existing type
        }
        $default = $default[0].';'.$type;
        foreach($icons as $k => $v){
            $return .= '<i class="' . explode(';', $v)[1] . ' fa-' . explode(';', $v)[0] . ($default==$v ? ' super-active' : '') . '"></i>';
        }
        $return .= '</div>';
        $return .= '<input type="hidden" name="'.$id.'" value="'.esc_attr($field['v']).'" id="field-'.$id.'" class="super-element-field" />';
        $return .= '</div>';
        return $return;
    
    }
    

    /**
     * Font Awesome 5.7.2  icons array
     * http://fontawesome.io/cheatsheet/
     * 
     * @version 5.7.2 
     * @date 16.03.2019.
     */
    public static function icons() {
        $solid_icons = array(
            '0;fas', '1;fas', '2;fas', '3;fas', '4;fas', '5;fas', '6;fas', '7;fas', '8;fas', '9;fas', 'a;fas', 'address-book;fas', 'address-card;fas', 'align-center;fas', 'align-justify;fas', 'align-left;fas', 'align-right;fas', 'anchor;fas', 'angle-down;fas', 'angle-left;fas', 'angle-right;fas', 'angle-up;fas', 'angles-down;fas', 'angles-left;fas', 'angles-right;fas', 'angles-up;fas', 'ankh;fas', 'apple-whole;fas', 'archway;fas', 'arrow-down;fas', 'arrow-down-1-9;fas', 'arrow-down-9-1;fas', 'arrow-down-a-z;fas', 'arrow-down-long;fas', 'arrow-down-short-wide;fas', 'arrow-down-wide-short;fas', 'arrow-down-z-a;fas', 'arrow-left;fas', 'arrow-left-long;fas', 'arrow-pointer;fas', 'arrow-right;fas', 'arrow-right-arrow-left;fas', 'arrow-right-from-bracket;fas', 'arrow-right-long;fas', 'arrow-right-to-bracket;fas', 'arrow-rotate-left;fas', 'arrow-rotate-right;fas', 'arrow-trend-down;fas', 'arrow-trend-up;fas', 'arrow-turn-down;fas', 'arrow-turn-up;fas', 'arrow-up;fas', 'arrow-up-1-9;fas', 'arrow-up-9-1;fas', 'arrow-up-a-z;fas', 'arrow-up-from-bracket;fas', 'arrow-up-long;fas', 'arrow-up-right-from-square;fas', 'arrow-up-short-wide;fas', 'arrow-up-wide-short;fas', 'arrow-up-z-a;fas', 'arrows-left-right;fas', 'arrows-rotate;fas', 'arrows-up-down;fas', 'arrows-up-down-left-right;fas', 'asterisk;fas', 'at;fas', 'atom;fas', 'audio-description;fas', 'austral-sign;fas', 'award;fas', 'b;fas', 'baby;fas', 'baby-carriage;fas', 'backward;fas', 'backward-fast;fas', 'backward-step;fas', 'bacon;fas', 'bacteria;fas', 'bacterium;fas', 'bag-shopping;fas', 'bahai;fas', 'baht-sign;fas', 'ban;fas', 'ban-smoking;fas', 'bandage;fas', 'barcode;fas', 'bars;fas', 'bars-progress;fas', 'bars-staggered;fas', 'baseball;fas', 'baseball-bat-ball;fas', 'basket-shopping;fas', 'basketball;fas', 'bath;fas', 'battery-empty;fas', 'battery-full;fas', 'battery-half;fas', 'battery-quarter;fas', 'battery-three-quarters;fas', 'bed;fas', 'bed-pulse;fas', 'beer-mug-empty;fas', 'bell;fas', 'bell-concierge;fas', 'bell-slash;fas', 'bezier-curve;fas', 'bicycle;fas', 'binoculars;fas', 'biohazard;fas', 'bitcoin-sign;fas', 'blender;fas', 'blender-phone;fas', 'blog;fas', 'bold;fas', 'bolt;fas', 'bolt-lightning;fas', 'bomb;fas', 'bone;fas', 'bong;fas', 'book;fas', 'book-atlas;fas', 'book-bible;fas', 'book-journal-whills;fas', 'book-medical;fas', 'book-open;fas', 'book-open-reader;fas', 'book-quran;fas', 'book-skull;fas', 'bookmark;fas', 'border-all;fas', 'border-none;fas', 'border-top-left;fas', 'bowling-ball;fas', 'box;fas', 'box-archive;fas', 'box-open;fas', 'box-tissue;fas', 'boxes-stacked;fas', 'braille;fas', 'brain;fas', 'brazilian-real-sign;fas', 'bread-slice;fas', 'briefcase;fas', 'briefcase-medical;fas', 'broom;fas', 'broom-ball;fas', 'brush;fas', 'bug;fas', 'bug-slash;fas', 'building;fas', 'building-columns;fas', 'bullhorn;fas', 'bullseye;fas', 'burger;fas', 'bus;fas', 'bus-simple;fas', 'business-time;fas', 'c;fas', 'cake-candles;fas', 'calculator;fas', 'calendar;fas', 'calendar-check;fas', 'calendar-day;fas', 'calendar-days;fas', 'calendar-minus;fas', 'calendar-plus;fas', 'calendar-week;fas', 'calendar-xmark;fas', 'camera;fas', 'camera-retro;fas', 'camera-rotate;fas', 'campground;fas', 'candy-cane;fas', 'cannabis;fas', 'capsules;fas', 'car;fas', 'car-battery;fas', 'car-crash;fas', 'car-rear;fas', 'car-side;fas', 'caravan;fas', 'caret-down;fas', 'caret-left;fas', 'caret-right;fas', 'caret-up;fas', 'carrot;fas', 'cart-arrow-down;fas', 'cart-flatbed;fas', 'cart-flatbed-suitcase;fas', 'cart-plus;fas', 'cart-shopping;fas', 'cash-register;fas', 'cat;fas', 'cedi-sign;fas', 'cent-sign;fas', 'certificate;fas', 'chair;fas', 'chalkboard;fas', 'chalkboard-user;fas', 'champagne-glasses;fas', 'charging-station;fas', 'chart-area;fas', 'chart-bar;fas', 'chart-column;fas', 'chart-gantt;fas', 'chart-line;fas', 'chart-pie;fas', 'check;fas', 'check-double;fas', 'check-to-slot;fas', 'cheese;fas', 'chess;fas', 'chess-bishop;fas', 'chess-board;fas', 'chess-king;fas', 'chess-knight;fas', 'chess-pawn;fas', 'chess-queen;fas', 'chess-rook;fas', 'chevron-down;fas', 'chevron-left;fas', 'chevron-right;fas', 'chevron-up;fas', 'child;fas', 'church;fas', 'circle;fas', 'circle-arrow-down;fas', 'circle-arrow-left;fas', 'circle-arrow-right;fas', 'circle-arrow-up;fas', 'circle-check;fas', 'circle-chevron-down;fas', 'circle-chevron-left;fas', 'circle-chevron-right;fas', 'circle-chevron-up;fas', 'circle-dollar-to-slot;fas', 'circle-dot;fas', 'circle-down;fas', 'circle-exclamation;fas', 'circle-h;fas', 'circle-half-stroke;fas', 'circle-info;fas', 'circle-left;fas', 'circle-minus;fas', 'circle-notch;fas', 'circle-pause;fas', 'circle-play;fas', 'circle-plus;fas', 'circle-question;fas', 'circle-radiation;fas', 'circle-right;fas', 'circle-stop;fas', 'circle-up;fas', 'circle-user;fas', 'circle-xmark;fas', 'city;fas', 'clapperboard;fas', 'clipboard;fas', 'clipboard-check;fas', 'clipboard-list;fas', 'clock;fas', 'clock-rotate-left;fas', 'clone;fas', 'closed-captioning;fas', 'cloud;fas', 'cloud-arrow-down;fas', 'cloud-arrow-up;fas', 'cloud-meatball;fas', 'cloud-moon;fas', 'cloud-moon-rain;fas', 'cloud-rain;fas', 'cloud-showers-heavy;fas', 'cloud-sun;fas', 'cloud-sun-rain;fas', 'clover;fas', 'code;fas', 'code-branch;fas', 'code-commit;fas', 'code-compare;fas', 'code-fork;fas', 'code-merge;fas', 'code-pull-request;fas', 'coins;fas', 'colon-sign;fas', 'comment;fas', 'comment-dollar;fas', 'comment-dots;fas', 'comment-medical;fas', 'comment-slash;fas', 'comment-sms;fas', 'comments;fas', 'comments-dollar;fas', 'compact-disc;fas', 'compass;fas', 'compass-drafting;fas', 'compress;fas', 'computer-mouse;fas', 'cookie;fas', 'cookie-bite;fas', 'copy;fas', 'copyright;fas', 'couch;fas', 'credit-card;fas', 'crop;fas', 'crop-simple;fas', 'cross;fas', 'crosshairs;fas', 'crow;fas', 'crown;fas', 'crutch;fas', 'cruzeiro-sign;fas', 'cube;fas', 'cubes;fas', 'd;fas', 'database;fas', 'delete-left;fas', 'democrat;fas', 'desktop;fas', 'dharmachakra;fas', 'diagram-next;fas', 'diagram-predecessor;fas', 'diagram-project;fas', 'diagram-successor;fas', 'diamond;fas', 'diamond-turn-right;fas', 'dice;fas', 'dice-d20;fas', 'dice-d6;fas', 'dice-five;fas', 'dice-four;fas', 'dice-one;fas', 'dice-six;fas', 'dice-three;fas', 'dice-two;fas', 'disease;fas', 'divide;fas', 'dna;fas', 'dog;fas', 'dollar-sign;fas', 'dolly;fas', 'dong-sign;fas', 'door-closed;fas', 'door-open;fas', 'dove;fas', 'down-left-and-up-right-to-center;fas', 'down-long;fas', 'download;fas', 'dragon;fas', 'draw-polygon;fas', 'droplet;fas', 'droplet-slash;fas', 'drum;fas', 'drum-steelpan;fas', 'drumstick-bite;fas', 'dumbbell;fas', 'dumpster;fas', 'dumpster-fire;fas', 'dungeon;fas', 'e;fas', 'ear-deaf;fas', 'ear-listen;fas', 'earth-africa;fas', 'earth-americas;fas', 'earth-asia;fas', 'earth-europe;fas', 'earth-oceania;fas', 'egg;fas', 'eject;fas', 'elevator;fas', 'ellipsis;fas', 'ellipsis-vertical;fas', 'envelope;fas', 'envelope-open;fas', 'envelope-open-text;fas', 'envelopes-bulk;fas', 'equals;fas', 'eraser;fas', 'ethernet;fas', 'euro-sign;fas', 'exclamation;fas', 'expand;fas', 'eye;fas', 'eye-dropper;fas', 'eye-low-vision;fas', 'eye-slash;fas', 'f;fas', 'face-angry;fas', 'face-dizzy;fas', 'face-flushed;fas', 'face-frown;fas', 'face-frown-open;fas', 'face-grimace;fas', 'face-grin;fas', 'face-grin-beam;fas', 'face-grin-beam-sweat;fas', 'face-grin-hearts;fas', 'face-grin-squint;fas', 'face-grin-squint-tears;fas', 'face-grin-stars;fas', 'face-grin-tears;fas', 'face-grin-tongue;fas', 'face-grin-tongue-squint;fas', 'face-grin-tongue-wink;fas', 'face-grin-wide;fas', 'face-grin-wink;fas', 'face-kiss;fas', 'face-kiss-beam;fas', 'face-kiss-wink-heart;fas', 'face-laugh;fas', 'face-laugh-beam;fas', 'face-laugh-squint;fas', 'face-laugh-wink;fas', 'face-meh;fas', 'face-meh-blank;fas', 'face-rolling-eyes;fas', 'face-sad-cry;fas', 'face-sad-tear;fas', 'face-smile;fas', 'face-smile-beam;fas', 'face-smile-wink;fas', 'face-surprise;fas', 'face-tired;fas', 'fan;fas', 'faucet;fas', 'fax;fas', 'feather;fas', 'feather-pointed;fas', 'file;fas', 'file-arrow-down;fas', 'file-arrow-up;fas', 'file-audio;fas', 'file-code;fas', 'file-contract;fas', 'file-csv;fas', 'file-excel;fas', 'file-export;fas', 'file-image;fas', 'file-import;fas', 'file-invoice;fas', 'file-invoice-dollar;fas', 'file-lines;fas', 'file-medical;fas', 'file-pdf;fas', 'file-powerpoint;fas', 'file-prescription;fas', 'file-signature;fas', 'file-video;fas', 'file-waveform;fas', 'file-word;fas', 'file-zipper;fas', 'fill;fas', 'fill-drip;fas', 'film;fas', 'filter;fas', 'filter-circle-dollar;fas', 'filter-circle-xmark;fas', 'fingerprint;fas', 'fire;fas', 'fire-extinguisher;fas', 'fire-flame-curved;fas', 'fire-flame-simple;fas', 'fish;fas', 'flag;fas', 'flag-checkered;fas', 'flag-usa;fas', 'flask;fas', 'floppy-disk;fas', 'florin-sign;fas', 'folder;fas', 'folder-minus;fas', 'folder-open;fas', 'folder-plus;fas', 'folder-tree;fas', 'font;fas', 'font-awesome;fas', 'football;fas', 'forward;fas', 'forward-fast;fas', 'forward-step;fas', 'franc-sign;fas', 'frog;fas', 'futbol;fas', 'g;fas', 'gamepad;fas', 'gas-pump;fas', 'gauge;fas', 'gauge-high;fas', 'gauge-simple;fas', 'gauge-simple-high;fas', 'gavel;fas', 'gear;fas', 'gears;fas', 'gem;fas', 'genderless;fas', 'ghost;fas', 'gift;fas', 'gifts;fas', 'glasses;fas', 'globe;fas', 'golf-ball-tee;fas', 'gopuram;fas', 'graduation-cap;fas', 'greater-than;fas', 'greater-than-equal;fas', 'grip;fas', 'grip-lines;fas', 'grip-lines-vertical;fas', 'grip-vertical;fas', 'guarani-sign;fas', 'guitar;fas', 'gun;fas', 'h;fas', 'hammer;fas', 'hamsa;fas', 'hand;fas', 'hand-back-fist;fas', 'hand-dots;fas', 'hand-fist;fas', 'hand-holding;fas', 'hand-holding-dollar;fas', 'hand-holding-droplet;fas', 'hand-holding-heart;fas', 'hand-holding-medical;fas', 'hand-lizard;fas', 'hand-middle-finger;fas', 'hand-peace;fas', 'hand-point-down;fas', 'hand-point-left;fas', 'hand-point-right;fas', 'hand-point-up;fas', 'hand-pointer;fas', 'hand-scissors;fas', 'hand-sparkles;fas', 'hand-spock;fas', 'hands;fas', 'hands-asl-interpreting;fas', 'hands-bubbles;fas', 'hands-clapping;fas', 'hands-holding;fas', 'hands-praying;fas', 'handshake;fas', 'handshake-angle;fas', 'handshake-simple-slash;fas', 'handshake-slash;fas', 'hanukiah;fas', 'hard-drive;fas', 'hashtag;fas', 'hat-cowboy;fas', 'hat-cowboy-side;fas', 'hat-wizard;fas', 'head-side-cough;fas', 'head-side-cough-slash;fas', 'head-side-mask;fas', 'head-side-virus;fas', 'heading;fas', 'headphones;fas', 'headphones-simple;fas', 'headset;fas', 'heart;fas', 'heart-crack;fas', 'heart-pulse;fas', 'helicopter;fas', 'helmet-safety;fas', 'highlighter;fas', 'hippo;fas', 'hockey-puck;fas', 'holly-berry;fas', 'horse;fas', 'horse-head;fas', 'hospital;fas', 'hospital-user;fas', 'hot-tub-person;fas', 'hotdog;fas', 'hotel;fas', 'hourglass;fas', 'hourglass-empty;fas', 'hourglass-end;fas', 'hourglass-start;fas', 'house;fas', 'house-chimney;fas', 'house-chimney-crack;fas', 'house-chimney-medical;fas', 'house-chimney-user;fas', 'house-chimney-window;fas', 'house-crack;fas', 'house-laptop;fas', 'house-medical;fas', 'house-user;fas', 'hryvnia-sign;fas', 'i;fas', 'i-cursor;fas', 'ice-cream;fas', 'icicles;fas', 'icons;fas', 'id-badge;fas', 'id-card;fas', 'id-card-clip;fas', 'igloo;fas', 'image;fas', 'image-portrait;fas', 'images;fas', 'inbox;fas', 'indent;fas', 'indian-rupee-sign;fas', 'industry;fas', 'infinity;fas', 'info;fas', 'italic;fas', 'j;fas', 'jedi;fas', 'jet-fighter;fas', 'joint;fas', 'k;fas', 'kaaba;fas', 'key;fas', 'keyboard;fas', 'khanda;fas', 'kip-sign;fas', 'kit-medical;fas', 'kiwi-bird;fas', 'l;fas', 'landmark;fas', 'language;fas', 'laptop;fas', 'laptop-code;fas', 'laptop-medical;fas', 'lari-sign;fas', 'layer-group;fas', 'leaf;fas', 'left-long;fas', 'left-right;fas', 'lemon;fas', 'less-than;fas', 'less-than-equal;fas', 'life-ring;fas', 'lightbulb;fas', 'link;fas', 'link-slash;fas', 'lira-sign;fas', 'list;fas', 'list-check;fas', 'list-ol;fas', 'list-ul;fas', 'litecoin-sign;fas', 'location-arrow;fas', 'location-crosshairs;fas', 'location-dot;fas', 'location-pin;fas', 'lock;fas', 'lock-open;fas', 'lungs;fas', 'lungs-virus;fas', 'm;fas', 'magnet;fas', 'magnifying-glass;fas', 'magnifying-glass-dollar;fas', 'magnifying-glass-location;fas', 'magnifying-glass-minus;fas', 'magnifying-glass-plus;fas', 'manat-sign;fas', 'map;fas', 'map-location;fas', 'map-location-dot;fas', 'map-pin;fas', 'marker;fas', 'mars;fas', 'mars-and-venus;fas', 'mars-double;fas', 'mars-stroke;fas', 'mars-stroke-right;fas', 'mars-stroke-up;fas', 'martini-glass;fas', 'martini-glass-citrus;fas', 'martini-glass-empty;fas', 'mask;fas', 'mask-face;fas', 'masks-theater;fas', 'maximize;fas', 'medal;fas', 'memory;fas', 'menorah;fas', 'mercury;fas', 'message;fas', 'meteor;fas', 'microchip;fas', 'microphone;fas', 'microphone-lines;fas', 'microphone-lines-slash;fas', 'microphone-slash;fas', 'microscope;fas', 'mill-sign;fas', 'minimize;fas', 'minus;fas', 'mitten;fas', 'mobile;fas', 'mobile-button;fas', 'mobile-screen-button;fas', 'money-bill;fas', 'money-bill-1;fas', 'money-bill-1-wave;fas', 'money-bill-wave;fas', 'money-check;fas', 'money-check-dollar;fas', 'monument;fas', 'moon;fas', 'mortar-pestle;fas', 'mosque;fas', 'motorcycle;fas', 'mountain;fas', 'mug-hot;fas', 'mug-saucer;fas', 'music;fas', 'n;fas', 'naira-sign;fas', 'network-wired;fas', 'neuter;fas', 'newspaper;fas', 'not-equal;fas', 'note-sticky;fas', 'notes-medical;fas', 'o;fas', 'object-group;fas', 'object-ungroup;fas', 'oil-can;fas', 'om;fas', 'otter;fas', 'outdent;fas', 'p;fas', 'pager;fas', 'paint-roller;fas', 'paintbrush;fas', 'palette;fas', 'pallet;fas', 'panorama;fas', 'paper-plane;fas', 'paperclip;fas', 'parachute-box;fas', 'paragraph;fas', 'passport;fas', 'paste;fas', 'pause;fas', 'paw;fas', 'peace;fas', 'pen;fas', 'pen-clip;fas', 'pen-fancy;fas', 'pen-nib;fas', 'pen-ruler;fas', 'pen-to-square;fas', 'pencil;fas', 'people-arrows-left-right;fas', 'people-carry-box;fas', 'pepper-hot;fas', 'percent;fas', 'person;fas', 'person-biking;fas', 'person-booth;fas', 'person-dots-from-line;fas', 'person-dress;fas', 'person-hiking;fas', 'person-praying;fas', 'person-running;fas', 'person-skating;fas', 'person-skiing;fas', 'person-skiing-nordic;fas', 'person-snowboarding;fas', 'person-swimming;fas', 'person-walking;fas', 'person-walking-with-cane;fas', 'peseta-sign;fas', 'peso-sign;fas', 'phone;fas', 'phone-flip;fas', 'phone-slash;fas', 'phone-volume;fas', 'photo-film;fas', 'piggy-bank;fas', 'pills;fas', 'pizza-slice;fas', 'place-of-worship;fas', 'plane;fas', 'plane-arrival;fas', 'plane-departure;fas', 'plane-slash;fas', 'play;fas', 'plug;fas', 'plus;fas', 'plus-minus;fas', 'podcast;fas', 'poo;fas', 'poo-storm;fas', 'poop;fas', 'power-off;fas', 'prescription;fas', 'prescription-bottle;fas', 'prescription-bottle-medical;fas', 'print;fas', 'pump-medical;fas', 'pump-soap;fas', 'puzzle-piece;fas', 'q;fas', 'qrcode;fas', 'question;fas', 'quote-left;fas', 'quote-right;fas', 'r;fas', 'radiation;fas', 'rainbow;fas', 'receipt;fas', 'record-vinyl;fas', 'rectangle-ad;fas', 'rectangle-list;fas', 'rectangle-xmark;fas', 'recycle;fas', 'registered;fas', 'repeat;fas', 'reply;fas', 'reply-all;fas', 'republican;fas', 'restroom;fas', 'retweet;fas', 'ribbon;fas', 'right-from-bracket;fas', 'right-left;fas', 'right-long;fas', 'right-to-bracket;fas', 'ring;fas', 'road;fas', 'robot;fas', 'rocket;fas', 'rotate;fas', 'rotate-left;fas', 'rotate-right;fas', 'route;fas', 'rss;fas', 'ruble-sign;fas', 'ruler;fas', 'ruler-combined;fas', 'ruler-horizontal;fas', 'ruler-vertical;fas', 'rupee-sign;fas', 'rupiah-sign;fas', 's;fas', 'sailboat;fas', 'satellite;fas', 'satellite-dish;fas', 'scale-balanced;fas', 'scale-unbalanced;fas', 'scale-unbalanced-flip;fas', 'school;fas', 'scissors;fas', 'screwdriver;fas', 'screwdriver-wrench;fas', 'scroll;fas', 'scroll-torah;fas', 'sd-card;fas', 'section;fas', 'seedling;fas', 'server;fas', 'shapes;fas', 'share;fas', 'share-from-square;fas', 'share-nodes;fas', 'shekel-sign;fas', 'shield;fas', 'shield-blank;fas', 'shield-virus;fas', 'ship;fas', 'shirt;fas', 'shoe-prints;fas', 'shop;fas', 'shop-slash;fas', 'shower;fas', 'shrimp;fas', 'shuffle;fas', 'shuttle-space;fas', 'sign-hanging;fas', 'signal;fas', 'signature;fas', 'signs-post;fas', 'sim-card;fas', 'sink;fas', 'sitemap;fas', 'skull;fas', 'skull-crossbones;fas', 'slash;fas', 'sleigh;fas', 'sliders;fas', 'smog;fas', 'smoking;fas', 'snowflake;fas', 'snowman;fas', 'snowplow;fas', 'soap;fas', 'socks;fas', 'solar-panel;fas', 'sort;fas', 'sort-down;fas', 'sort-up;fas', 'spa;fas', 'spaghetti-monster-flying;fas', 'spell-check;fas', 'spider;fas', 'spinner;fas', 'splotch;fas', 'spoon;fas', 'spray-can;fas', 'spray-can-sparkles;fas', 'square;fas', 'square-arrow-up-right;fas', 'square-caret-down;fas', 'square-caret-left;fas', 'square-caret-right;fas', 'square-caret-up;fas', 'square-check;fas', 'square-envelope;fas', 'square-full;fas', 'square-h;fas', 'square-minus;fas', 'square-parking;fas', 'square-pen;fas', 'square-phone;fas', 'square-phone-flip;fas', 'square-plus;fas', 'square-poll-horizontal;fas', 'square-poll-vertical;fas', 'square-root-variable;fas', 'square-rss;fas', 'square-share-nodes;fas', 'square-up-right;fas', 'square-xmark;fas', 'stairs;fas', 'stamp;fas', 'star;fas', 'star-and-crescent;fas', 'star-half;fas', 'star-half-stroke;fas', 'star-of-david;fas', 'star-of-life;fas', 'sterling-sign;fas', 'stethoscope;fas', 'stop;fas', 'stopwatch;fas', 'stopwatch-20;fas', 'store;fas', 'store-slash;fas', 'street-view;fas', 'strikethrough;fas', 'stroopwafel;fas', 'subscript;fas', 'suitcase;fas', 'suitcase-medical;fas', 'suitcase-rolling;fas', 'sun;fas', 'superscript;fas', 'swatchbook;fas', 'synagogue;fas', 'syringe;fas', 't;fas', 'table;fas', 'table-cells;fas', 'table-cells-large;fas', 'table-columns;fas', 'table-list;fas', 'table-tennis-paddle-ball;fas', 'tablet;fas', 'tablet-button;fas', 'tablet-screen-button;fas', 'tablets;fas', 'tachograph-digital;fas', 'tag;fas', 'tags;fas', 'tape;fas', 'taxi;fas', 'teeth;fas', 'teeth-open;fas', 'temperature-empty;fas', 'temperature-full;fas', 'temperature-half;fas', 'temperature-high;fas', 'temperature-low;fas', 'temperature-quarter;fas', 'temperature-three-quarters;fas', 'tenge-sign;fas', 'terminal;fas', 'text-height;fas', 'text-slash;fas', 'text-width;fas', 'thermometer;fas', 'thumbs-down;fas', 'thumbs-up;fas', 'thumbtack;fas', 'ticket;fas', 'ticket-simple;fas', 'timeline;fas', 'toggle-off;fas', 'toggle-on;fas', 'toilet;fas', 'toilet-paper;fas', 'toilet-paper-slash;fas', 'toolbox;fas', 'tooth;fas', 'torii-gate;fas', 'tower-broadcast;fas', 'tractor;fas', 'trademark;fas', 'traffic-light;fas', 'trailer;fas', 'train;fas', 'train-subway;fas', 'train-tram;fas', 'transgender;fas', 'trash;fas', 'trash-arrow-up;fas', 'trash-can;fas', 'trash-can-arrow-up;fas', 'tree;fas', 'triangle-exclamation;fas', 'trophy;fas', 'truck;fas', 'truck-fast;fas', 'truck-medical;fas', 'truck-monster;fas', 'truck-moving;fas', 'truck-pickup;fas', 'truck-ramp-box;fas', 'tty;fas', 'turkish-lira-sign;fas', 'turn-down;fas', 'turn-up;fas', 'tv;fas', 'u;fas', 'umbrella;fas', 'umbrella-beach;fas', 'underline;fas', 'universal-access;fas', 'unlock;fas', 'unlock-keyhole;fas', 'up-down;fas', 'up-down-left-right;fas', 'up-long;fas', 'up-right-and-down-left-from-center;fas', 'up-right-from-square;fas', 'upload;fas', 'user;fas', 'user-astronaut;fas', 'user-check;fas', 'user-clock;fas', 'user-doctor;fas', 'user-gear;fas', 'user-graduate;fas', 'user-group;fas', 'user-injured;fas', 'user-large;fas', 'user-large-slash;fas', 'user-lock;fas', 'user-minus;fas', 'user-ninja;fas', 'user-nurse;fas', 'user-pen;fas', 'user-plus;fas', 'user-secret;fas', 'user-shield;fas', 'user-slash;fas', 'user-tag;fas', 'user-tie;fas', 'user-xmark;fas', 'users;fas', 'users-gear;fas', 'users-slash;fas', 'utensils;fas', 'v;fas', 'van-shuttle;fas', 'vault;fas', 'vector-square;fas', 'venus;fas', 'venus-double;fas', 'venus-mars;fas', 'vest;fas', 'vest-patches;fas', 'vial;fas', 'vials;fas', 'video;fas', 'video-slash;fas', 'vihara;fas', 'virus;fas', 'virus-covid;fas', 'virus-covid-slash;fas', 'virus-slash;fas', 'viruses;fas', 'voicemail;fas', 'volleyball;fas', 'volume-high;fas', 'volume-low;fas', 'volume-off;fas', 'volume-xmark;fas', 'vr-cardboard;fas', 'w;fas', 'wallet;fas', 'wand-magic;fas', 'wand-magic-sparkles;fas', 'wand-sparkles;fas', 'warehouse;fas', 'water;fas', 'water-ladder;fas', 'wave-square;fas', 'weight-hanging;fas', 'weight-scale;fas', 'wheelchair;fas', 'whiskey-glass;fas', 'wifi;fas', 'wind;fas', 'window-maximize;fas', 'window-minimize;fas', 'window-restore;fas', 'wine-bottle;fas', 'wine-glass;fas', 'wine-glass-empty;fas', 'won-sign;fas', 'wrench;fas', 'x;fas', 'x-ray;fas', 'xmark;fas', 'y;fas', 'yen-sign;fas', 'yin-yang;fas', 'z;fas'
        );
        $regular_icons = array(
            'address-book;far', 'address-card;far', 'bell;far', 'bell-slash;far', 'bookmark;far', 'building;far', 'calendar;far', 'calendar-check;far', 'calendar-days;far', 'calendar-minus;far', 'calendar-plus;far', 'calendar-xmark;far', 'chart-bar;far', 'chess-bishop;far', 'chess-king;far', 'chess-knight;far', 'chess-pawn;far', 'chess-queen;far', 'chess-rook;far', 'circle;far', 'circle-check;far', 'circle-dot;far', 'circle-down;far', 'circle-left;far', 'circle-pause;far', 'circle-play;far', 'circle-question;far', 'circle-right;far', 'circle-stop;far', 'circle-up;far', 'circle-user;far', 'circle-xmark;far', 'clipboard;far', 'clock;far', 'clone;far', 'closed-captioning;far', 'comment;far', 'comment-dots;far', 'comments;far', 'compass;far', 'copy;far', 'copyright;far', 'credit-card;far', 'envelope;far', 'envelope-open;far', 'eye;far', 'eye-slash;far', 'face-angry;far', 'face-dizzy;far', 'face-flushed;far', 'face-frown;far', 'face-frown-open;far', 'face-grimace;far', 'face-grin;far', 'face-grin-beam;far', 'face-grin-beam-sweat;far', 'face-grin-hearts;far', 'face-grin-squint;far', 'face-grin-squint-tears;far', 'face-grin-stars;far', 'face-grin-tears;far', 'face-grin-tongue;far', 'face-grin-tongue-squint;far', 'face-grin-tongue-wink;far', 'face-grin-wide;far', 'face-grin-wink;far', 'face-kiss;far', 'face-kiss-beam;far', 'face-kiss-wink-heart;far', 'face-laugh;far', 'face-laugh-beam;far', 'face-laugh-squint;far', 'face-laugh-wink;far', 'face-meh;far', 'face-meh-blank;far', 'face-rolling-eyes;far', 'face-sad-cry;far', 'face-sad-tear;far', 'face-smile;far', 'face-smile-beam;far', 'face-smile-wink;far', 'face-surprise;far', 'face-tired;far', 'file;far', 'file-audio;far', 'file-code;far', 'file-excel;far', 'file-image;far', 'file-lines;far', 'file-pdf;far', 'file-powerpoint;far', 'file-video;far', 'file-word;far', 'file-zipper;far', 'flag;far', 'floppy-disk;far', 'folder;far', 'folder-open;far', 'font-awesome;far', 'futbol;far', 'gem;far', 'hand;far', 'hand-back-fist;far', 'hand-lizard;far', 'hand-peace;far', 'hand-point-down;far', 'hand-point-left;far', 'hand-point-right;far', 'hand-point-up;far', 'hand-pointer;far', 'hand-scissors;far', 'hand-spock;far', 'handshake;far', 'hard-drive;far', 'heart;far', 'hospital;far', 'hourglass;far', 'id-badge;far', 'id-card;far', 'image;far', 'images;far', 'keyboard;far', 'lemon;far', 'life-ring;far', 'lightbulb;far', 'map;far', 'message;far', 'money-bill-1;far', 'moon;far', 'newspaper;far', 'note-sticky;far', 'object-group;far', 'object-ungroup;far', 'paper-plane;far', 'paste;far', 'pen-to-square;far', 'rectangle-list;far', 'rectangle-xmark;far', 'registered;far', 'share-from-square;far', 'snowflake;far', 'square;far', 'square-caret-down;far', 'square-caret-left;far', 'square-caret-right;far', 'square-caret-up;far', 'square-check;far', 'square-full;far', 'square-minus;far', 'square-plus;far', 'star;far', 'star-half;far', 'star-half-stroke;far', 'sun;far', 'thumbs-down;far', 'thumbs-up;far', 'trash-can;far', 'user;far', 'window-maximize;far', 'window-minimize;far', 'window-restore;far'
        );
        $brand_icons = array(
            '42-group;fab', '500px;fab', 'accessible-icon;fab', 'accusoft;fab', 'adn;fab', 'adversal;fab', 'affiliatetheme;fab', 'airbnb;fab', 'algolia;fab', 'alipay;fab', 'amazon;fab', 'amazon-pay;fab', 'amilia;fab', 'android;fab', 'angellist;fab', 'angrycreative;fab', 'angular;fab', 'app-store;fab', 'app-store-ios;fab', 'apper;fab', 'apple;fab', 'apple-pay;fab', 'artstation;fab', 'asymmetrik;fab', 'atlassian;fab', 'audible;fab', 'autoprefixer;fab', 'avianex;fab', 'aviato;fab', 'aws;fab', 'bandcamp;fab', 'battle-net;fab', 'behance;fab', 'behance-square;fab', 'bilibili;fab', 'bimobject;fab', 'bitbucket;fab', 'bitcoin;fab', 'bity;fab', 'black-tie;fab', 'blackberry;fab', 'blogger;fab', 'blogger-b;fab', 'bluetooth;fab', 'bluetooth-b;fab', 'bootstrap;fab', 'bots;fab', 'btc;fab', 'buffer;fab', 'buromobelexperte;fab', 'buy-n-large;fab', 'buysellads;fab', 'canadian-maple-leaf;fab', 'cc-amazon-pay;fab', 'cc-amex;fab', 'cc-apple-pay;fab', 'cc-diners-club;fab', 'cc-discover;fab', 'cc-jcb;fab', 'cc-mastercard;fab', 'cc-paypal;fab', 'cc-stripe;fab', 'cc-visa;fab', 'centercode;fab', 'centos;fab', 'chrome;fab', 'chromecast;fab', 'cloudflare;fab', 'cloudscale;fab', 'cloudsmith;fab', 'cloudversify;fab', 'cmplid;fab', 'codepen;fab', 'codiepie;fab', 'confluence;fab', 'connectdevelop;fab', 'contao;fab', 'cotton-bureau;fab', 'cpanel;fab', 'creative-commons;fab', 'creative-commons-by;fab', 'creative-commons-nc;fab', 'creative-commons-nc-eu;fab', 'creative-commons-nc-jp;fab', 'creative-commons-nd;fab', 'creative-commons-pd;fab', 'creative-commons-pd-alt;fab', 'creative-commons-remix;fab', 'creative-commons-sa;fab', 'creative-commons-sampling;fab', 'creative-commons-sampling-plus;fab', 'creative-commons-share;fab', 'creative-commons-zero;fab', 'critical-role;fab', 'css3;fab', 'css3-alt;fab', 'cuttlefish;fab', 'd-and-d;fab', 'd-and-d-beyond;fab', 'dailymotion;fab', 'dashcube;fab', 'deezer;fab', 'delicious;fab', 'deploydog;fab', 'deskpro;fab', 'dev;fab', 'deviantart;fab', 'dhl;fab', 'diaspora;fab', 'digg;fab', 'digital-ocean;fab', 'discord;fab', 'discourse;fab', 'dochub;fab', 'docker;fab', 'draft2digital;fab', 'dribbble;fab', 'dribbble-square;fab', 'dropbox;fab', 'drupal;fab', 'dyalog;fab', 'earlybirds;fab', 'ebay;fab', 'edge;fab', 'edge-legacy;fab', 'elementor;fab', 'ello;fab', 'ember;fab', 'empire;fab', 'envira;fab', 'erlang;fab', 'ethereum;fab', 'etsy;fab', 'evernote;fab', 'expeditedssl;fab', 'facebook;fab', 'facebook-f;fab', 'facebook-messenger;fab', 'facebook-square;fab', 'fantasy-flight-games;fab', 'fedex;fab', 'fedora;fab', 'figma;fab', 'firefox;fab', 'firefox-browser;fab', 'first-order;fab', 'first-order-alt;fab', 'firstdraft;fab', 'flickr;fab', 'flipboard;fab', 'fly;fab', 'font-awesome;fab', 'fonticons;fab', 'fonticons-fi;fab', 'fort-awesome;fab', 'fort-awesome-alt;fab', 'forumbee;fab', 'foursquare;fab', 'free-code-camp;fab', 'freebsd;fab', 'fulcrum;fab', 'galactic-republic;fab', 'galactic-senate;fab', 'get-pocket;fab', 'gg;fab', 'gg-circle;fab', 'git;fab', 'git-alt;fab', 'git-square;fab', 'github;fab', 'github-alt;fab', 'github-square;fab', 'gitkraken;fab', 'gitlab;fab', 'gitter;fab', 'glide;fab', 'glide-g;fab', 'gofore;fab', 'golang;fab', 'goodreads;fab', 'goodreads-g;fab', 'google;fab', 'google-drive;fab', 'google-pay;fab', 'google-play;fab', 'google-plus;fab', 'google-plus-g;fab', 'google-plus-square;fab', 'google-wallet;fab', 'gratipay;fab', 'grav;fab', 'gripfire;fab', 'grunt;fab', 'guilded;fab', 'gulp;fab', 'hacker-news;fab', 'hacker-news-square;fab', 'hackerrank;fab', 'hashnode;fab', 'hips;fab', 'hire-a-helper;fab', 'hive;fab', 'hooli;fab', 'hornbill;fab', 'hotjar;fab', 'houzz;fab', 'html5;fab', 'hubspot;fab', 'ideal;fab', 'imdb;fab', 'instagram;fab', 'instagram-square;fab', 'instalod;fab', 'intercom;fab', 'internet-explorer;fab', 'invision;fab', 'ioxhost;fab', 'itch-io;fab', 'itunes;fab', 'itunes-note;fab', 'java;fab', 'jedi-order;fab', 'jenkins;fab', 'jira;fab', 'joget;fab', 'joomla;fab', 'js;fab', 'js-square;fab', 'jsfiddle;fab', 'kaggle;fab', 'keybase;fab', 'keycdn;fab', 'kickstarter;fab', 'kickstarter-k;fab', 'korvue;fab', 'laravel;fab', 'lastfm;fab', 'lastfm-square;fab', 'leanpub;fab', 'less;fab', 'line;fab', 'linkedin;fab', 'linkedin-in;fab', 'linode;fab', 'linux;fab', 'lyft;fab', 'magento;fab', 'mailchimp;fab', 'mandalorian;fab', 'markdown;fab', 'mastodon;fab', 'maxcdn;fab', 'mdb;fab', 'medapps;fab', 'medium;fab', 'medrt;fab', 'meetup;fab', 'megaport;fab', 'mendeley;fab', 'microblog;fab', 'microsoft;fab', 'mix;fab', 'mixcloud;fab', 'mixer;fab', 'mizuni;fab', 'modx;fab', 'monero;fab', 'napster;fab', 'neos;fab', 'nimblr;fab', 'node;fab', 'node-js;fab', 'npm;fab', 'ns8;fab', 'nutritionix;fab', 'octopus-deploy;fab', 'odnoklassniki;fab', 'odnoklassniki-square;fab', 'old-republic;fab', 'opencart;fab', 'openid;fab', 'opera;fab', 'optin-monster;fab', 'orcid;fab', 'osi;fab', 'padlet;fab', 'page4;fab', 'pagelines;fab', 'palfed;fab', 'patreon;fab', 'paypal;fab', 'perbyte;fab', 'periscope;fab', 'phabricator;fab', 'phoenix-framework;fab', 'phoenix-squadron;fab', 'php;fab', 'pied-piper;fab', 'pied-piper-alt;fab', 'pied-piper-hat;fab', 'pied-piper-pp;fab', 'pied-piper-square;fab', 'pinterest;fab', 'pinterest-p;fab', 'pinterest-square;fab', 'pix;fab', 'playstation;fab', 'product-hunt;fab', 'pushed;fab', 'python;fab', 'qq;fab', 'quinscape;fab', 'quora;fab', 'r-project;fab', 'raspberry-pi;fab', 'ravelry;fab', 'react;fab', 'reacteurope;fab', 'readme;fab', 'rebel;fab', 'red-river;fab', 'reddit;fab', 'reddit-alien;fab', 'reddit-square;fab', 'redhat;fab', 'renren;fab', 'replyd;fab', 'researchgate;fab', 'resolving;fab', 'rev;fab', 'rocketchat;fab', 'rockrms;fab', 'rust;fab', 'safari;fab', 'salesforce;fab', 'sass;fab', 'schlix;fab', 'scribd;fab', 'searchengin;fab', 'sellcast;fab', 'sellsy;fab', 'servicestack;fab', 'shirtsinbulk;fab', 'shopify;fab', 'shopware;fab', 'simplybuilt;fab', 'sistrix;fab', 'sith;fab', 'sitrox;fab', 'sketch;fab', 'skyatlas;fab', 'skype;fab', 'slack;fab', 'slideshare;fab', 'snapchat;fab', 'snapchat-square;fab', 'soundcloud;fab', 'sourcetree;fab', 'speakap;fab', 'speaker-deck;fab', 'spotify;fab', 'square-font-awesome;fab', 'square-font-awesome-stroke;fab', 'squarespace;fab', 'stack-exchange;fab', 'stack-overflow;fab', 'stackpath;fab', 'staylinked;fab', 'steam;fab', 'steam-square;fab', 'steam-symbol;fab', 'sticker-mule;fab', 'strava;fab', 'stripe;fab', 'stripe-s;fab', 'studiovinari;fab', 'stumbleupon;fab', 'stumbleupon-circle;fab', 'superpowers;fab', 'supple;fab', 'suse;fab', 'swift;fab', 'symfony;fab', 'teamspeak;fab', 'telegram;fab', 'tencent-weibo;fab', 'the-red-yeti;fab', 'themeco;fab', 'themeisle;fab', 'think-peaks;fab', 'tiktok;fab', 'trade-federation;fab', 'trello;fab', 'tumblr;fab', 'tumblr-square;fab', 'twitch;fab', 'twitter;fab', 'twitter-square;fab', 'typo3;fab', 'uber;fab', 'ubuntu;fab', 'uikit;fab', 'umbraco;fab', 'uncharted;fab', 'uniregistry;fab', 'unity;fab', 'unsplash;fab', 'untappd;fab', 'ups;fab', 'usb;fab', 'usps;fab', 'ussunnah;fab', 'vaadin;fab', 'viacoin;fab', 'viadeo;fab', 'viadeo-square;fab', 'viber;fab', 'vimeo;fab', 'vimeo-square;fab', 'vimeo-v;fab', 'vine;fab', 'vk;fab', 'vnv;fab', 'vuejs;fab', 'watchman-monitoring;fab', 'waze;fab', 'weebly;fab', 'weibo;fab', 'weixin;fab', 'whatsapp;fab', 'whatsapp-square;fab', 'whmcs;fab', 'wikipedia-w;fab', 'windows;fab', 'wirsindhandwerk;fab', 'wix;fab', 'wizards-of-the-coast;fab', 'wodu;fab', 'wolf-pack-battalion;fab', 'wordpress;fab', 'wordpress-simple;fab', 'wpbeginner;fab', 'wpexplorer;fab', 'wpforms;fab', 'wpressr;fab', 'xbox;fab', 'xing;fab', 'xing-square;fab', 'y-combinator;fab', 'yahoo;fab', 'yammer;fab', 'yandex;fab', 'yandex-international;fab', 'yarn;fab', 'yelp;fab', 'yoast;fab', 'youtube;fab', 'youtube-square;fab', 'zhihu;fab'
        );
        $icon_array = array_merge($solid_icons, $regular_icons);
        $icon_array = array_merge($icon_array, $brand_icons);
        $icon_array = apply_filters( 'super_icons',  $icon_array );
        return array_unique( $icon_array );
    }    
    
}
endif;
