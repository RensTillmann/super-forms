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
        
    // @since 4.8.0 - Tab/Accordion Element
    // Tab/Accordion Items
    public static function tab_items( $id, $field, $data ) {
        $translating = $_POST['translating'];
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
                if(isset($field['default'][$k])){
                    $v = array_merge($v, $field['default'][$k]);
                }
                $return .= '<div class="super-multi-items super-tab-item">';
                    if($translating!=='true'){
                        $return .= '<div class="sorting">';
                            $return .= '<span class="up"><i class="fas fa-arrow-up"></i></span>';
                            $return .= '<span class="down"><i class="fas fa-arrow-down"></i></span>';
                        $return .= '</div>';
                    }
                    $return .= '<input type="text" placeholder="' . esc_html__( 'Title', 'super-forms' ) . '" value="' . esc_attr( $v['title'] ) . '" name="title">';
                    $return .= '<textarea placeholder="' . esc_html__( 'Description', 'super-forms' ) . '" name="desc">' . esc_attr( $v['desc'] ) . '</textarea>';
                    if($translating!=='true'){
                        $return .= '<i class="add super-add-item fas fa-plus"></i>';
                        $return .= '<i class="delete fas fa-trash-alt"></i>';
                        if( !isset( $v['image'] ) ) $v['image'] = '';
                        $return .= '<div class="image-field browse-images">';
                        $return .= '<span class="button super-insert-image"><i class="far fa-image"></i></span>';
                        $return .= '<ul class="image-preview">';
                        $image = wp_get_attachment_image_src( $v['image'], 'thumbnail' );
                        $image = !empty( $image[0] ) ? $image[0] : '';
                        if( !empty( $image ) ) {
                            $return .= '<li data-file="' . $v['image'] . '">';
                            $return .= '<div class="image"><img src="' . $image . '"></div>';
                            $return .= '<input type="number" placeholder="' . esc_html__( 'width', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['max_width'] ) ) . '" name="max_width">';
                            $return .= '<span>px</span>';
                            $return .= '<input type="number" placeholder="' . esc_html__( 'height', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['max_height'] ) ) . '" name="max_height">';
                            $return .= '<span>px</span>';
                            $return .= '<a href="#" class="delete">Delete</a>';
                            $return .= '</li>';
                        }
                        $return .= '</ul>';
                        $return .= '<input type="hidden" name="image" value="' . $v['image'] . '" />';
                        $return .= '</div>';
                    }

                $return .= '</div>';
            }
            $return .= '<textarea name="' . $id . '" class="element-field multi-items-json">' . json_encode( $data[$id] ) . '</textarea>';
        }
        return $return;
    }

    // @since 3.8.0 - field to reset submission counter for users
    public static function reset_user_submission_count( $id, $field ) {
        $return  = '<div class="input">';
            $return .= '<span class="super-button reset-user-submission-counter delete">' . esc_html__( 'Reset Submission Counter for Users', 'super-forms' ) . '</span>';
        $return .= '</div>';
        return $return;
    }

    // @since 3.4.0 - field to reset submission counter
    public static function reset_submission_count( $id, $field ) {
        $return  = '<div class="input">';
            $return .= '<input type="number" id="field-' . $id . '" name="' . $id . '" class="element-field" value="' . esc_attr( stripslashes( $field['default'] ) ) . '" />';
            $return .= '<span class="super-button reset-submission-counter delete">' . esc_html__( 'Reset Submission Counter', 'super-forms' ) . '</span>';
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
            $return .= '<select id="field-'.$id.'" name="'.$id.'" data-value="'.$field['default'].'" class="element-field previously-created-fields '.$multiple.'"'.$multiple.$filter.'>';
            foreach($field['values'] as $k => $v ) {
                $selected = '';
                if($field['default']==$k){
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
            $return .= '<select id="field-'.$id.'" name="'.$id.'" class="element-field previously-created-product-fields '.$multiple.'"'.$multiple.$filter.'>';
            foreach($field['values'] as $k => $v ) {
                $selected = '';
                if($field['default']==$k){
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
        $translating = $_POST['translating'];
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
                if(isset($field['default'][$k])){
                    $v = array_merge($v, $field['default'][$k]);
                }
                $return .= '<div class="super-multi-items super-dropdown-item">';
                    if( !isset( $v['checked'] ) ) $v['checked'] = 'false';
                    if($translating!=='true'){
                        $return .= '<input data-prev="'.$v['checked'].'" ' . ($id=='radio_items' || $id=='autosuggest_items' ? 'type="radio"' : 'type="checkbox"') . ( ($v['checked']==1 || $v['checked']=='true') ? ' checked="checked"' : '' ) . '">';
                        $return .= '<div class="sorting">';
                            $return .= '<span class="up"><i class="fas fa-arrow-up"></i></span>';
                            $return .= '<span class="down"><i class="fas fa-arrow-down"></i></span>';
                        $return .= '</div>';
                    }
                    $return .= '<input type="text" placeholder="' . esc_html__( 'Label', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['label'] ) ) . '" name="label">';
                    $return .= '<input type="text" ' . ($translating=='true' ? 'disabled="disabled" ' : '') . 'placeholder="' . esc_html__( 'Value', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['value'] ) ) . '" name="value">';
                    
                    if($translating!=='true'){
                        $return .= '<i class="add super-add-item fas fa-plus"></i>';
                        $return .= '<i class="delete fas fa-trash-alt"></i>';

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
                                $return .= '<div class="image"><img src="' . $image . '"></div>';
                                $return .= '<input type="number" placeholder="' . esc_html__( 'width', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['max_width'] ) ) . '" name="max_width">';
                                $return .= '<span>px</span>';
                                $return .= '<input type="number" placeholder="' . esc_html__( 'height', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['max_height'] ) ) . '" name="max_height">';
                                $return .= '<span>px</span>';
                                $return .= '<a href="#" class="delete">Delete</a>';
                                $return .= '</li>';
                            }
                            $return .= '</ul>';
                            $return .= '<input type="hidden" name="image" value="' . $v['image'] . '" />';
                            $return .= '</div>';
                        }
                    }

                $return .= '</div>';
            }
            $return .= '<textarea name="' . $id . '" class="element-field multi-items-json">' . json_encode( $data[$id] ) . '</textarea>';
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
        $image = wp_get_attachment_image_src( $field['default'], 'thumbnail' );
        $image = !empty( $image[0] ) ? $image[0] : '';
        if( !empty( $image ) ) {
            $return .= '<li data-file="' . $field['default'] . '">';
            $return .= '<div class="image"><img src="' . $image . '"></div>';
            $return .= '<a href="#" class="delete">Delete</a>';
            $return .= '</li>';
        }
        $return .= '</ul>';
        $return .= '<input type="hidden" name="' . $id . '" value="' . esc_attr( $field['default'] ) . '" id="field-' . $id . '" class="element-field" />';
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
        $files = explode(',', $field['default']);
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
                $return .= '<div class="image"><img src="' . $icon . '"></div>';
                $return .= '<a href="' . $url . '">' . $filename . '</a>';
                $return .= '<a href="#" class="delete">Delete</a>';
                $return .= '</li>';
            }
        }
        $return .= '</ul>';
        $return .= '<input type="hidden" name="' . $id . '" value="' . esc_attr( $field['default'] ) . '" id="field-' . $id . '" class="element-field" />';
        $return .= '</div>';
        return $return;
    }

    //TinyMCE
    public static function tiny_mce($id, $field){
        $return = '';
        $return .= '<div class="super-element super-shortcode-tinymce super-shortcode-target-content">';
        $content = stripslashes($field['default']);
        ob_start();
        wp_editor( $content, $id , array('editor_class' => 'super-advanced-textarea super-tinymce', 'media_buttons' => true ) );
        $return .= ob_get_clean();
        $return .= '</div>';
        return $return;
    }
    
    //Number slider
    public static function slider($id, $field){
        $return  = '<div class="slider-field">';
        $return .= '<input type="text" name="'.$id.'" value="'.esc_attr($field['default']).'" id="field-'.$id.'" data-steps="'.$field['steps'].'" data-min="'.$field['min'].'" data-max="'.$field['max'].'" class="element-field" />';
        $return .= '</div>';
        return $return;
    }
    
    //Input field    
    public static function text( $id, $field ) {
        $return  = '<div class="input">';
            $return .= '<input type="text" id="field-' . $id . '"';
            if( isset( $field['placeholder'] ) ) {
                $return .= ( $field['placeholder']!='' ? 'placeholder="' . $field['placeholder'] . '"' : '' );
            }
            if( isset( $field['required'] ) ) {
                $return .= ( $field['required']==true ? 'required="true"' : '');
            }
            if( isset( $field['maxlength'] ) ) {
                $return .= ( $field['maxlength'] > 0 ? 'maxlength="' . $field['maxlength'] . '"' : '' );
            }
            $return .= 'name="' . $id . '" class="element-field" value="' . esc_attr( stripslashes( $field['default'] ) ) . '" />';
        $return .= '</div>';
        return $return;
    }

    // @since 4.0.0  - conditional check field (2 fields next to eachother)
    public static function conditional_check( $id, $field ) {
        $return  = '<div class="super-conditional-check">';
            $defaults = explode(',', $field['default']);
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
            $return .= 'name="' . $id . '_1" class="element-field" value="' . esc_attr( stripslashes( $defaults[0] ) ) . '" />';

            $return .= '<select name="' . $id . '_2">';
            $return .= '<option' . ($defaults[1]=='==' ? ' selected="selected"' : '') . ' value="==">== (' . esc_html__( 'Equal', 'super-forms' ) . '</option>';
            $return .= '<option' . ($defaults[1]=='!=' ? ' selected="selected"' : '') . ' value="!=">!= (' . esc_html__( 'Not equals', 'super-forms' ) . ')</option>';

            $return .= '<input type="text"';
            // get second part of placeholder
            $return .= ( $placeholders[1]!='' ? 'placeholder="' . $placeholders[1] . '"' : '' );
            $return .= 'name="' . $id . '_3" class="element-field" value="' . esc_attr( stripslashes( $defaults[2] ) ) . '" />';

            $return .= '<input type="hidden" name="' . $id . '" class="element-field" value="' . esc_attr( stripslashes( $field['default'] ) ) . '" />';
        $return .= '</div>';
        return $return;
    }


    //Checkbox field
    public static function checkbox( $id, $field ) {
        $return = '';
        $return .= '<div class="super-checkbox">';
        foreach( $field['values'] as $k => $v ) {
            $return .= '<label><input type="checkbox" value="' . $k . '" ' . ($field['default']==$k ? 'checked="checked"' : '') . '>' . $v . '</label>';
        }
        $return .= '</div>';
        $return .= '<input type="hidden" name="' . $id . '" value="' . esc_attr( $field['default'] ) . '" id="field-' . $id . '" class="element-field" />';
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
            $return .= 'name="'.$id.'" class="element-field" value="' . esc_attr( $field['default'] ) . '" />';
        $return .= '</div>';
        return $return;
    }

    //Textarea  
    public static function textarea( $id, $field ) {
        $field = wp_parse_args( $field, array(
            'rows'    => 3,
            'default' => ''
        ) );
        $return = '<textarea name="' . $id . '" id="super-generator-attr-' . $id . '" ';
        if(isset($field['placeholder'])){
            $return .= ($field['placeholder']!='' ? 'placeholder="'.$field['placeholder'].'"' : '');
        }        
        if(isset($field['required'])){
            $return .= ($field['required']==true ? 'required="true" ' : '');
        }
        $value = esc_textarea(stripslashes($field['default']));
        $return .= 'rows="' . $field['rows'] . '" class="element-field">' . $value . '</textarea>';
        return $return;
    }
    
    // address_auto_complete
    public static function address_auto_populate( $id, $field, $data ) {
        $mappings = array(
            'street_number' => esc_html__( 'Street number', 'super-forms' ),
            'street_name' => esc_html__( 'Street name', 'super-forms' ),
            'street_name_number' => esc_html__( 'Street name + nr', 'super-forms' ),
            'street_number_name' => esc_html__( 'Street nr + name', 'super-forms' ),
            'city' => esc_html__( 'City name', 'super-forms' ),
            'state' => esc_html__( 'State/Province', 'super-forms' ),
            'postal_code' => esc_html__( 'Postal code', 'super-forms' ),
            'country' => esc_html__( 'Country name', 'super-forms' ),
            'municipality' => esc_html__( 'Municipality', 'super-forms' )
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
                        $return .= '<option value="">- retrieve method -</option>';
                        $return .= '<option value="long"' . ($type=='long' ? ' selected="selected"' : '') . '>Long name (default)</option>';
                        $return .= '<option value="short"' . ($type=='short' ? ' selected="selected"' : '') . '>Short name</option>';
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
                        $return .= '<option value="long">Long name (default)</option>';
                        $return .= '<option value="short">Short name</option>';
                    $return .= '</select>';
                $return .= '</div>';
            }
        }
        if( is_array( $field['default'] ) ) $field['default'] = json_encode( $field['default'] );
        $return .= '<textarea name="' . $id . '" class="element-field multi-items-json">' . $field['default'] . '</textarea>';
        return $return;
    }

    //Conditions
    public static function conditions( $id, $field, $data ) {
        $options = array(
            'contains'=>'?? Contains',
            'equal'=>'== Equal',
            'not_equal'=>'!= Not equal',
            'greater_than'=>'> Greater than',
            'less_than'=>'<  Less than',
            'greater_than_or_equal'=>'>= Greater than or equal to',
            'less_than_or_equal'=>'<= Less than or equal',            
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
                        $return .= '<option' . ('and'==$v['and_method'] ? ' selected="selected"' : '') . '  value="and">AND</option>';
                        $return .= '<option' . ('or'==$v['and_method'] ? ' selected="selected"' : '') . '  value="or">OR</option>';
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Field {tag}" value="' . $field_and . '" name="conditional_field_and">';
                    $return .= '<select name="conditional_logic_and">';
                        $return .= '<option selected="selected" value="">---</option>';
                        foreach( $options as $ok => $ov ) {
                            $return .= '<option' . ($ok==$v['logic_and'] ? ' selected="selected"' : '') . ' value="' . $ok . '">' . $ov . '</option>';
                        }
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Value" value="' . $v['value_and'] . '" name="conditional_value_and">';
                    $return .= '<i class="add fas fa-plus"></i>';
                    $return .= '<i class="delete fas fa-trash-alt" style="visibility: hidden;"></i>';
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
                    $return .= '<option value="and">AND</option>';
                    $return .= '<option value="or">OR</option>';
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Field {tag}" value="" name="conditional_field_and">';
                $return .= '<select name="conditional_logic_and">';
                    $return .= '<option selected="selected" value="">---</option>';
                    foreach( $options as $ok => $ov ) {
                        $return .= '<option value="' . $ok . '">' . $ov . '</option>';
                    }
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Value" value="" name="conditional_value_and">';
                $return .= '<i class="add fas fa-plus"></i>';
                $return .= '<i class="delete fas fa-trash-alt" style="visibility: hidden;"></i>';
                $return .= '<span class="line-break"></span>';
            $return .= '</div>';
        }
        if( is_array( $field['default'] ) ) $field['default'] = json_encode( $field['default'] );
        $return .= '<textarea name="' . $id . '" class="element-field multi-items-json">' . $field['default'] . '</textarea>';
        return $return;
    }

    // @since 1.2.7 Variable Conditions
    public static function variable_conditions( $id, $field, $data ) {
        $options = array(
            'contains'=>'?? Contains',
            'equal'=>'== Equal',
            'not_equal'=>'!= Not equal',
            'greater_than'=>'> Greater than',
            'less_than'=>'<  Less than',
            'greater_than_or_equal'=>'>= Greater than or equal to',
            'less_than_or_equal'=>'<= Less than or equal',            
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
                        $return .= '<option' . ('and'==$v['and_method'] ? ' selected="selected"' : '') . '  value="and">AND</option>';
                        $return .= '<option' . ('or'==$v['and_method'] ? ' selected="selected"' : '') . '  value="or">OR</option>';
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Field {tag}" value="' . $field_and . '" name="conditional_field_and">';
                    $return .= '<select name="conditional_logic_and">';
                        $return .= '<option selected="selected" value="">---</option>';
                        foreach( $options as $ok => $ov ) {
                            $return .= '<option' . ($ok==$v['logic_and'] ? ' selected="selected"' : '') . ' value="' . $ok . '">' . $ov . '</option>';
                        }
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Value" value="' . $v['value_and'] . '" name="conditional_value_and">';
                    $return .= '<i class="add fas fa-plus"></i>';
                    $return .= '<i class="delete fas fa-trash-alt" style="visibility: hidden;"></i>';
                    $return .= '<span class="line-break"></span>';
                    $return .= '<p>' . esc_html__( 'When above conditions are met set following value:', 'super-forms' ) . '</p>';
                    $return .= '<textarea placeholder="New value" name="conditional_new_value">' . stripslashes( $v['new_value'] ) . '</textarea>';
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
                    $return .= '<option value="and">AND</option>';
                    $return .= '<option value="or">OR</option>';
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Field {tag}" value="" name="conditional_field_and">';
                $return .= '<select name="conditional_logic_and">';
                    $return .= '<option selected="selected" value="">---</option>';
                    foreach( $options as $ok => $ov ) {
                        $return .= '<option value="' . $ok . '">' . $ov . '</option>';
                    }
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Value" value="" name="conditional_value_and">';
                $return .= '<i class="add fas fa-plus"></i>';
                $return .= '<i class="delete fas fa-trash-alt" style="visibility: hidden;"></i>';
                $return .= '<span class="line-break"></span>';
                $return .= '<p>' . esc_html__( 'When above conditions are met set following value:', 'super-forms' ) . '</p>';
                $return .= '<textarea placeholder="New value" value="" name="conditional_new_value"></textarea>';
                $return .= '</div>';
        }
        if( is_array( $field['default'] ) ) $field['default'] = json_encode( $field['default'] );
        $return .= '<textarea name="' . $id . '" class="element-field multi-items-json">' . $field['default'] . '</textarea>';
        return $return;
    }
    

 
    //Time field    
    public static function time($id, $field){
        $return  = '<div class="input">';
            $return .= '<input type="text" id="field-'.$id.'"';
            if(isset($field['required'])){
                $return .= ($field['required']==true ? 'required="true"' : '');
            }
            $return .= 'name="'.$id.'" data-format="H:i" data-step="5" class="element-field super-timepicker" value="'.esc_attr($field['default']).'" />';
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
                if( isset($field['default']) && $field['default']==$k ) {
                    $selected = ' checked="checked"';
                    $class = ' super-active';
                }
                $return .= '<div class="super-image-select-option' . $class . '">';
                    $return .= '<input type="radio"' . $selected . ' value="' . esc_attr( $k ) . '" id="field-' . $id . '" name="' . $id . '" class="element-field"' . $filter . '>';
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
            $return .= '<select id="field-' . $id . '" name="' . $id . '" class="element-field ' . $multiple . '"' . $multiple . $filter . '>';
            foreach( $field['values'] as $k => $v ) {
                $selected = '';
                if( ( isset( $field['multiple'] ) ) && ( $field['default']!='' ) ) {
                    if( in_array( $k, $field['default'] ) ) {
                        $selected = ' selected="selected"';
                    }
                }else{
                    if( isset($field['default']) && $field['default']==$k ) {
                        $selected = ' selected="selected"';
                    }
                }
                $return .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
            }
            $return .= '</select>';
            if( isset( $field['info'] ) ) $return .= '<p>' . $field['info'] . '</p>';
        $return .= '</div>';
        return $return;
    }
    
    //Color picker
    public static function color($id, $field){
        $return  = '<div class="super-color-picker-container">';
            $return .= '<div class="super-color-picker">';
                $return .= '<input type="text" id="field-'.$id.'" name="'.$id.'" class="element-field" value="'.esc_attr($field['default']).'" />';
            $return .= '</div>';
        $return .= '</div>';
        return $return;
    }
    
    //Multi Color picker
    public static function multicolor($id, $field){
        $return = '<div class="input">';
        foreach($field['colors'] as $k => $v){
            $return .= '<div class="super-color-picker-container">';
                if(isset($v['label'])) $return .= '<div class="super-color-picker-label">'.$v['label'].'</div>';
                $return .= '<div class="super-color-picker">';
                    $return .= '<input type="text" id="field-'.$k.'" name="'.$k.'" class="element-field" value="'.esc_attr($v['default']).'" />';
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

        $default = explode(';', $field['default']);
        $type = 'fas';
        if(isset($default[1])){
            $type = $default[1]; // use the existing type
        }
        $default = $default[0].';'.$type;
        foreach($icons as $k => $v){
            $return .= '<i class="' . explode(';', $v)[1] . ' fa-' . explode(';', $v)[0] . ($default==$v ? ' active' : '') . '"></i>';
        }
        $return .= '</div>';
        $return .= '<input type="hidden" name="'.$id.'" value="'.esc_attr($field['default']).'" id="field-'.$id.'" class="element-field" />';
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

        // Regex to find and replace in near future when new icons are added (DO NOT DELETE)
        // <li(.*?)fa-
        // replace with:
        // '

        // "(.*?)</li>
        // replace with:
        // ;far',

        $solid_icons = array(
            'ad;fas','address-book;fas','address-card;fas','adjust;fas','air-freshener;fas','align-center;fas','align-justify;fas','align-left;fas','align-right;fas','allergies;fas','ambulance;fas','american-sign-language-interpreting;fas','anchor;fas','angle-double-down;fas','angle-double-left;fas','angle-double-right;fas','angle-double-up;fas','angle-down;fas','angle-left;fas','angle-right;fas','angle-up;fas','angry;fas','ankh;fas','apple-alt;fas','archive;fas','archway;fas','arrow-alt-circle-down;fas','arrow-alt-circle-left;fas','arrow-alt-circle-right;fas','arrow-alt-circle-up;fas','arrow-circle-down;fas','arrow-circle-left;fas','arrow-circle-right;fas','arrow-circle-up;fas','arrow-down;fas','arrow-left;fas','arrow-right;fas','arrow-up;fas','arrows-alt;fas','arrows-alt-h;fas','arrows-alt-v;fas','assistive-listening-systems;fas','asterisk;fas','at;fas','atlas;fas','atom;fas','audio-description;fas','award;fas','baby;fas','baby-carriage;fas','backspace;fas','backward;fas','bacon;fas','balance-scale;fas','ban;fas','band-aid;fas','barcode;fas','bars;fas','baseball-ball;fas','basketball-ball;fas','bath;fas','battery-empty;fas','battery-full;fas','battery-half;fas','battery-quarter;fas','battery-three-quarters;fas','bed;fas','beer;fas','bell;fas','bell-slash;fas','bezier-curve;fas','bible;fas','bicycle;fas','binoculars;fas','biohazard;fas','birthday-cake;fas','blender;fas','blender-phone;fas','blind;fas','blog;fas','bold;fas','bolt;fas','bomb;fas','bone;fas','bong;fas','book;fas','book-dead;fas','book-medical;fas','book-open;fas','book-reader;fas','bookmark;fas','bowling-ball;fas','box;fas','box-open;fas','boxes;fas','braille;fas','brain;fas','bread-slice;fas','briefcase;fas','briefcase-medical;fas','broadcast-tower;fas','broom;fas','brush;fas','bug;fas','building;fas','bullhorn;fas','bullseye;fas','burn;fas','bus;fas','bus-alt;fas','business-time;fas','calculator;fas','calendar;fas','calendar-alt;fas','calendar-check;fas','calendar-day;fas','calendar-minus;fas','calendar-plus;fas','calendar-times;fas','calendar-week;fas','camera;fas','camera-retro;fas','campground;fas','candy-cane;fas','cannabis;fas','capsules;fas','car;fas','car-alt;fas','car-battery;fas','car-crash;fas','car-side;fas','caret-down;fas','caret-left;fas','caret-right;fas','caret-square-down;fas','caret-square-left;fas','caret-square-right;fas','caret-square-up;fas','caret-up;fas','carrot;fas','cart-arrow-down;fas','cart-plus;fas','cash-register;fas','cat;fas','certificate;fas','chair;fas','chalkboard;fas','chalkboard-teacher;fas','charging-station;fas','chart-area;fas','chart-bar;fas','chart-line;fas','chart-pie;fas','check;fas','check-circle;fas','check-double;fas','check-square;fas','cheese;fas','chess;fas','chess-bishop;fas','chess-board;fas','chess-king;fas','chess-knight;fas','chess-pawn;fas','chess-queen;fas','chess-rook;fas','chevron-circle-down;fas','chevron-circle-left;fas','chevron-circle-right;fas','chevron-circle-up;fas','chevron-down;fas','chevron-left;fas','chevron-right;fas','chevron-up;fas','child;fas','church;fas','circle;fas','circle-notch;fas','city;fas','clinic-medical;fas','clipboard;fas','clipboard-check;fas','clipboard-list;fas','clock;fas','clone;fas','closed-captioning;fas','cloud;fas','cloud-download-alt;fas','cloud-meatball;fas','cloud-moon;fas','cloud-moon-rain;fas','cloud-rain;fas','cloud-showers-heavy;fas','cloud-sun;fas','cloud-sun-rain;fas','cloud-upload-alt;fas','cocktail;fas','code;fas','code-branch;fas','coffee;fas','cog;fas','cogs;fas','coins;fas','columns;fas','comment;fas','comment-alt;fas','comment-dollar;fas','comment-dots;fas','comment-medical;fas','comment-slash;fas','comments;fas','comments-dollar;fas','compact-disc;fas','compass;fas','compress;fas','compress-arrows-alt;fas','concierge-bell;fas','cookie;fas','cookie-bite;fas','copy;fas','copyright;fas','couch;fas','credit-card;fas','crop;fas','crop-alt;fas','cross;fas','crosshairs;fas','crow;fas','crown;fas','crutch;fas','cube;fas','cubes;fas','cut;fas','database;fas','deaf;fas','democrat;fas','desktop;fas','dharmachakra;fas','diagnoses;fas','dice;fas','dice-d20;fas','dice-d6;fas','dice-five;fas','dice-four;fas','dice-one;fas','dice-six;fas','dice-three;fas','dice-two;fas','digital-tachograph;fas','directions;fas','divide;fas','dizzy;fas','dna;fas','dog;fas','dollar-sign;fas','dolly;fas','dolly-flatbed;fas','donate;fas','door-closed;fas','door-open;fas','dot-circle;fas','dove;fas','download;fas','drafting-compass;fas','dragon;fas','draw-polygon;fas','drum;fas','drum-steelpan;fas','drumstick-bite;fas','dumbbell;fas','dumpster;fas','dumpster-fire;fas','dungeon;fas','edit;fas','egg;fas','eject;fas','ellipsis-h;fas','ellipsis-v;fas','envelope;fas','envelope-open;fas','envelope-open-text;fas','envelope-square;fas','equals;fas','eraser;fas','ethernet;fas','euro-sign;fas','exchange-alt;fas','exclamation;fas','exclamation-circle;fas','exclamation-triangle;fas','expand;fas','expand-arrows-alt;fas','external-link-alt;fas','external-link-square-alt;fas','eye;fas','eye-dropper;fas','eye-slash;fas','fast-backward;fas','fast-forward;fas','fax;fas','feather;fas','feather-alt;fas','female;fas','fighter-jet;fas','file;fas','file-alt;fas','file-archive;fas','file-audio;fas','file-code;fas','file-contract;fas','file-csv;fas','file-download;fas','file-excel;fas','file-export;fas','file-image;fas','file-import;fas','file-invoice;fas','file-invoice-dollar;fas','file-medical;fas','file-medical-alt;fas','file-pdf;fas','file-powerpoint;fas','file-prescription;fas','file-signature;fas','file-upload;fas','file-video;fas','file-word;fas','fill;fas','fill-drip;fas','film;fas','filter;fas','fingerprint;fas','fire;fas','fire-alt;fas','fire-extinguisher;fas','first-aid;fas','fish;fas','fist-raised;fas','flag;fas','flag-checkered;fas','flag-usa;fas','flask;fas','flushed;fas','folder;fas','folder-minus;fas','folder-open;fas','folder-plus;fas','font;fas','football-ball;fas','forward;fas','frog;fas','frown;fas','frown-open;fas','funnel-dollar;fas','futbol;fas','gamepad;fas','gas-pump;fas','gavel;fas','gem;fas','genderless;fas','ghost;fas','gift;fas','gifts;fas','glass-cheers;fas','glass-martini;fas','glass-martini-alt;fas','glass-whiskey;fas','glasses;fas','globe;fas','globe-africa;fas','globe-americas;fas','globe-asia;fas','globe-europe;fas','golf-ball;fas','gopuram;fas','graduation-cap;fas','greater-than;fas','greater-than-equal;fas','grimace;fas','grin;fas','grin-alt;fas','grin-beam;fas','grin-beam-sweat;fas','grin-hearts;fas','grin-squint;fas','grin-squint-tears;fas','grin-stars;fas','grin-tears;fas','grin-tongue;fas','grin-tongue-squint;fas','grin-tongue-wink;fas','grin-wink;fas','grip-horizontal;fas','grip-lines;fas','grip-lines-vertical;fas','grip-vertical;fas','guitar;fas','h-square;fas','hamburger;fas','hammer;fas','hamsa;fas','hand-holding;fas','hand-holding-heart;fas','hand-holding-usd;fas','hand-lizard;fas','hand-middle-finger;fas','hand-paper;fas','hand-peace;fas','hand-point-down;fas','hand-point-left;fas','hand-point-right;fas','hand-point-up;fas','hand-pointer;fas','hand-rock;fas','hand-scissors;fas','hand-spock;fas','hands;fas','hands-helping;fas','handshake;fas','hanukiah;fas','hard-hat;fas','hashtag;fas','hat-wizard;fas','haykal;fas','hdd;fas','heading;fas','headphones;fas','headphones-alt;fas','headset;fas','heart;fas','heart-broken;fas','heartbeat;fas','helicopter;fas','highlighter;fas','hiking;fas','hippo;fas','history;fas','hockey-puck;fas','holly-berry;fas','home;fas','horse;fas','horse-head;fas','hospital;fas','hospital-alt;fas','hospital-symbol;fas','hot-tub;fas','hotdog;fas','hotel;fas','hourglass;fas','hourglass-end;fas','hourglass-half;fas','hourglass-start;fas','house-damage;fas','hryvnia;fas','i-cursor;fas','ice-cream;fas','icicles;fas','id-badge;fas','id-card;fas','id-card-alt;fas','igloo;fas','image;fas','images;fas','inbox;fas','indent;fas','industry;fas','infinity;fas','info;fas','info-circle;fas','italic;fas','jedi;fas','joint;fas','journal-whills;fas','kaaba;fas','key;fas','keyboard;fas','khanda;fas','kiss;fas','kiss-beam;fas','kiss-wink-heart;fas','kiwi-bird;fas','landmark;fas','language;fas','laptop;fas','laptop-code;fas','laptop-medical;fas','laugh;fas','laugh-beam;fas','laugh-squint;fas','laugh-wink;fas','layer-group;fas','leaf;fas','lemon;fas','less-than;fas','less-than-equal;fas','level-down-alt;fas','level-up-alt;fas','life-ring;fas','lightbulb;fas','link;fas','lira-sign;fas','list;fas','list-alt;fas','list-ol;fas','list-ul;fas','location-arrow;fas','lock;fas','lock-open;fas','long-arrow-alt-down;fas','long-arrow-alt-left;fas','long-arrow-alt-right;fas','long-arrow-alt-up;fas','low-vision;fas','luggage-cart;fas','magic;fas','magnet;fas','mail-bulk;fas','male;fas','map;fas','map-marked;fas','map-marked-alt;fas','map-marker;fas','map-marker-alt;fas','map-pin;fas','map-signs;fas','marker;fas','mars;fas','mars-double;fas','mars-stroke;fas','mars-stroke-h;fas','mars-stroke-v;fas','mask;fas','medal;fas','medkit;fas','meh;fas','meh-blank;fas','meh-rolling-eyes;fas','memory;fas','menorah;fas','mercury;fas','meteor;fas','microchip;fas','microphone;fas','microphone-alt;fas','microphone-alt-slash;fas','microphone-slash;fas','microscope;fas','minus;fas','minus-circle;fas','minus-square;fas','mitten;fas','mobile;fas','mobile-alt;fas','money-bill;fas','money-bill-alt;fas','money-bill-wave;fas','money-bill-wave-alt;fas','money-check;fas','money-check-alt;fas','monument;fas','moon;fas','mortar-pestle;fas','mosque;fas','motorcycle;fas','mountain;fas','mouse-pointer;fas','mug-hot;fas','music;fas','network-wired;fas','neuter;fas','newspaper;fas','not-equal;fas','notes-medical;fas','object-group;fas','object-ungroup;fas','oil-can;fas','om;fas','otter;fas','outdent;fas','pager;fas','paint-brush;fas','paint-roller;fas','palette;fas','pallet;fas','paper-plane;fas','paperclip;fas','parachute-box;fas','paragraph;fas','parking;fas','passport;fas','pastafarianism;fas','paste;fas','pause;fas','pause-circle;fas','paw;fas','peace;fas','pen;fas','pen-alt;fas','pen-fancy;fas','pen-nib;fas','pen-square;fas','pencil-alt;fas','pencil-ruler;fas','people-carry;fas','pepper-hot;fas','percent;fas','percentage;fas','person-booth;fas','phone;fas','phone-slash;fas','phone-square;fas','phone-volume;fas','piggy-bank;fas','pills;fas','pizza-slice;fas','place-of-worship;fas','plane;fas','plane-arrival;fas','plane-departure;fas','play;fas','play-circle;fas','plug;fas','plus;fas','plus-circle;fas','plus-square;fas','podcast;fas','poll;fas','poll-h;fas','poo;fas','poo-storm;fas','poop;fas','portrait;fas','pound-sign;fas','power-off;fas','pray;fas','praying-hands;fas','prescription;fas','prescription-bottle;fas','prescription-bottle-alt;fas','print;fas','procedures;fas','project-diagram;fas','puzzle-piece;fas','qrcode;fas','question;fas','question-circle;fas','quidditch;fas','quote-left;fas','quote-right;fas','quran;fas','radiation;fas','radiation-alt;fas','rainbow;fas','random;fas','receipt;fas','recycle;fas','redo;fas','redo-alt;fas','registered;fas','reply;fas','reply-all;fas','republican;fas','restroom;fas','retweet;fas','ribbon;fas','ring;fas','road;fas','robot;fas','rocket;fas','route;fas','rss;fas','rss-square;fas','ruble-sign;fas','ruler;fas','ruler-combined;fas','ruler-horizontal;fas','ruler-vertical;fas','running;fas','rupee-sign;fas','sad-cry;fas','sad-tear;fas','satellite;fas','satellite-dish;fas','save;fas','school;fas','screwdriver;fas','scroll;fas','sd-card;fas','search;fas','search-dollar;fas','search-location;fas','search-minus;fas','search-plus;fas','seedling;fas','server;fas','shapes;fas','share;fas','share-alt;fas','share-alt-square;fas','share-square;fas','shekel-sign;fas','shield-alt;fas','ship;fas','shipping-fast;fas','shoe-prints;fas','shopping-bag;fas','shopping-basket;fas','shopping-cart;fas','shower;fas','shuttle-van;fas','sign;fas','sign-in-alt;fas','sign-language;fas','sign-out-alt;fas','signal;fas','signature;fas','sim-card;fas','sitemap;fas','skating;fas','skiing;fas','skiing-nordic;fas','skull;fas','skull-crossbones;fas','slash;fas','sleigh;fas','sliders-h;fas','smile;fas','smile-beam;fas','smile-wink;fas','smog;fas','smoking;fas','smoking-ban;fas','sms;fas','snowboarding;fas','snowflake;fas','snowman;fas','snowplow;fas','socks;fas','solar-panel;fas','sort;fas','sort-alpha-down;fas','sort-alpha-up;fas','sort-amount-down;fas','sort-amount-up;fas','sort-down;fas','sort-numeric-down;fas','sort-numeric-up;fas','sort-up;fas','spa;fas','space-shuttle;fas','spider;fas','spinner;fas','splotch;fas','spray-can;fas','square;fas','square-full;fas','square-root-alt;fas','stamp;fas','star;fas','star-and-crescent;fas','star-half;fas','star-half-alt;fas','star-of-david;fas','star-of-life;fas','step-backward;fas','step-forward;fas','stethoscope;fas','sticky-note;fas','stop;fas','stop-circle;fas','stopwatch;fas','store;fas','store-alt;fas','stream;fas','street-view;fas','strikethrough;fas','stroopwafel;fas','subscript;fas','subway;fas','suitcase;fas','suitcase-rolling;fas','sun;fas','superscript;fas','surprise;fas','swatchbook;fas','swimmer;fas','swimming-pool;fas','synagogue;fas','sync;fas','sync-alt;fas','syringe;fas','table;fas','table-tennis;fas','tablet;fas','tablet-alt;fas','tablets;fas','tachometer-alt;fas','tag;fas','tags;fas','tape;fas','tasks;fas','taxi;fas','teeth;fas','teeth-open;fas','temperature-high;fas','temperature-low;fas','tenge;fas','terminal;fas','text-height;fas','text-width;fas','th;fas','th-large;fas','th-list;fas','theater-masks;fas','thermometer;fas','thermometer-empty;fas','thermometer-full;fas','thermometer-half;fas','thermometer-quarter;fas','thermometer-three-quarters;fas','thumbs-down;fas','thumbs-up;fas','thumbtack;fas','ticket-alt;fas','times;fas','times-circle;fas','tint;fas','tint-slash;fas','tired;fas','toggle-off;fas','toggle-on;fas','toilet;fas','toilet-paper;fas','toolbox;fas','tools;fas','tooth;fas','torah;fas','torii-gate;fas','tractor;fas','trademark;fas','traffic-light;fas','train;fas','tram;fas','transgender;fas','transgender-alt;fas','trash;fas','trash-alt;fas','trash-restore;fas','trash-restore-alt;fas','tree;fas','trophy;fas','truck;fas','truck-loading;fas','truck-monster;fas','truck-moving;fas','truck-pickup;fas','tshirt;fas','tty;fas','tv;fas','umbrella;fas','umbrella-beach;fas','underline;fas','undo;fas','undo-alt;fas','universal-access;fas','university;fas','unlink;fas','unlock;fas','unlock-alt;fas','upload;fas','user;fas','user-alt;fas','user-alt-slash;fas','user-astronaut;fas','user-check;fas','user-circle;fas','user-clock;fas','user-cog;fas','user-edit;fas','user-friends;fas','user-graduate;fas','user-injured;fas','user-lock;fas','user-md;fas','user-minus;fas','user-ninja;fas','user-nurse;fas','user-plus;fas','user-secret;fas','user-shield;fas','user-slash;fas','user-tag;fas','user-tie;fas','user-times;fas','users;fas','users-cog;fas','utensil-spoon;fas','utensils;fas','vector-square;fas','venus;fas','venus-double;fas','venus-mars;fas','vial;fas','vials;fas','video;fas','video-slash;fas','vihara;fas','volleyball-ball;fas','volume-down;fas','volume-mute;fas','volume-off;fas','volume-up;fas','vote-yea;fas','vr-cardboard;fas','walking;fas','wallet;fas','warehouse;fas','water;fas','weight;fas','weight-hanging;fas','wheelchair;fas','wifi;fas','wind;fas','window-close;fas','window-maximize;fas','window-minimize;fas','window-restore;fas','wine-bottle;fas','wine-glass;fas','wine-glass-alt;fas','won-sign;fas','wrench;fas','x-ray;fas','yen-sign;fas','yin-yang;fas'
        );
        $regular_icons = array(
            'address-book;far','address-card;far','angry;far','arrow-alt-circle-down;far','arrow-alt-circle-left;far','arrow-alt-circle-right;far','arrow-alt-circle-up;far','bell;far','bell-slash;far','bookmark;far','building;far','calendar;far','calendar-alt;far','calendar-check;far','calendar-minus;far','calendar-plus;far','calendar-times;far','caret-square-down;far','caret-square-left;far','caret-square-right;far','caret-square-up;far','chart-bar;far','check-circle;far','check-square;far','circle;far','clipboard;far','clock;far','clone;far','closed-captioning;far','comment;far','comment-alt;far','comment-dots;far','comments;far','compass;far','copy;far','copyright;far','credit-card;far','dizzy;far','dot-circle;far','edit;far','envelope;far','envelope-open;far','eye;far','eye-slash;far','file;far','file-alt;far','file-archive;far','file-audio;far','file-code;far','file-excel;far','file-image;far','file-pdf;far','file-powerpoint;far','file-video;far','file-word;far','flag;far','flushed;far','folder;far','folder-open;far','frown;far','frown-open;far','futbol;far','gem;far','grimace;far','grin;far','grin-alt;far','grin-beam;far','grin-beam-sweat;far','grin-hearts;far','grin-squint;far','grin-squint-tears;far','grin-stars;far','grin-tears;far','grin-tongue;far','grin-tongue-squint;far','grin-tongue-wink;far','grin-wink;far','hand-lizard;far','hand-paper;far','hand-peace;far','hand-point-down;far','hand-point-left;far','hand-point-right;far','hand-point-up;far','hand-pointer;far','hand-rock;far','hand-scissors;far','hand-spock;far','handshake;far','hdd;far','heart;far','hospital;far','hourglass;far','id-badge;far','id-card;far','image;far','images;far','keyboard;far','kiss;far','kiss-beam;far','kiss-wink-heart;far','laugh;far','laugh-beam;far','laugh-squint;far','laugh-wink;far','lemon;far','life-ring;far','lightbulb;far','list-alt;far','map;far','meh;far','meh-blank;far','meh-rolling-eyes;far','minus-square;far','money-bill-alt;far','moon;far','newspaper;far','object-group;far','object-ungroup;far','paper-plane;far','pause-circle;far','play-circle;far','plus-square;far','question-circle;far','registered;far','sad-cry;far','sad-tear;far','save;far','share-square;far','smile;far','smile-beam;far','smile-wink;far','snowflake;far','square;far','star;far','star-half;far','sticky-note;far','stop-circle;far','sun;far','surprise;far','thumbs-down;far','thumbs-up;far','times-circle;far','tired;far','trash-alt;far','user;far','user-circle;far','window-close;far','window-maximize;far','window-minimize;far','window-restore;far'        
        );
        $brand_icons = array(
            '500px;fab','accessible-icon;fab','accusoft;fab','acquisitions-incorporated;fab','adn;fab','adobe;fab','adversal;fab','affiliatetheme;fab','airbnb;fab','algolia;fab','alipay;fab','amazon;fab','amazon-pay;fab','amilia;fab','android;fab','angellist;fab','angrycreative;fab','angular;fab','app-store;fab','app-store-ios;fab','apper;fab','apple;fab','apple-pay;fab','artstation;fab','asymmetrik;fab','atlassian;fab','audible;fab','autoprefixer;fab','avianex;fab','aviato;fab','aws;fab','bandcamp;fab','battle-net;fab','behance;fab','behance-square;fab','bimobject;fab','bitbucket;fab','bitcoin;fab','bity;fab','black-tie;fab','blackberry;fab','blogger;fab','blogger-b;fab','bluetooth;fab','bluetooth-b;fab','bootstrap;fab','btc;fab','buffer;fab','buromobelexperte;fab','buysellads;fab','canadian-maple-leaf;fab','cc-amazon-pay;fab','cc-amex;fab','cc-apple-pay;fab','cc-diners-club;fab','cc-discover;fab','cc-jcb;fab','cc-mastercard;fab','cc-paypal;fab','cc-stripe;fab','cc-visa;fab','centercode;fab','centos;fab','chrome;fab','chromecast;fab','cloudscale;fab','cloudsmith;fab','cloudversify;fab','codepen;fab','codiepie;fab','confluence;fab','connectdevelop;fab','contao;fab','cpanel;fab','creative-commons;fab','creative-commons-by;fab','creative-commons-nc;fab','creative-commons-nc-eu;fab','creative-commons-nc-jp;fab','creative-commons-nd;fab','creative-commons-pd;fab','creative-commons-pd-alt;fab','creative-commons-remix;fab','creative-commons-sa;fab','creative-commons-sampling;fab','creative-commons-sampling-plus;fab','creative-commons-share;fab','creative-commons-zero;fab','critical-role;fab','css3;fab','css3-alt;fab','cuttlefish;fab','d-and-d;fab','d-and-d-beyond;fab','dashcube;fab','delicious;fab','deploydog;fab','deskpro;fab','dev;fab','deviantart;fab','dhl;fab','diaspora;fab','digg;fab','digital-ocean;fab','discord;fab','discourse;fab','dochub;fab','docker;fab','draft2digital;fab','dribbble;fab','dribbble-square;fab','dropbox;fab','drupal;fab','dyalog;fab','earlybirds;fab','ebay;fab','edge;fab','elementor;fab','ello;fab','ember;fab','empire;fab','envira;fab','erlang;fab','ethereum;fab','etsy;fab','evernote;fab','expeditedssl;fab','facebook;fab','facebook-f;fab','facebook-messenger;fab','facebook-square;fab','fantasy-flight-games;fab','fedex;fab','fedora;fab','figma;fab','firefox;fab','first-order;fab','first-order-alt;fab','firstdraft;fab','flickr;fab','flipboard;fab','fly;fab','font-awesome;fab','font-awesome-alt;fab','font-awesome-flag;fab','fonticons;fab','fonticons-fi;fab','fort-awesome;fab','fort-awesome-alt;fab','forumbee;fab','foursquare;fab','free-code-camp;fab','freebsd;fab','fulcrum;fab','galactic-republic;fab','galactic-senate;fab','get-pocket;fab','gg;fab','gg-circle;fab','git;fab','git-alt;fab','git-square;fab','github;fab','github-alt;fab','github-square;fab','gitkraken;fab','gitlab;fab','gitter;fab','glide;fab','glide-g;fab','gofore;fab','goodreads;fab','goodreads-g;fab','google;fab','google-drive;fab','google-play;fab','google-plus;fab','google-plus-g;fab','google-plus-square;fab','google-wallet;fab','gratipay;fab','grav;fab','gripfire;fab','grunt;fab','gulp;fab','hacker-news;fab','hacker-news-square;fab','hackerrank;fab','hips;fab','hire-a-helper;fab','hooli;fab','hornbill;fab','hotjar;fab','houzz;fab','html5;fab','hubspot;fab','imdb;fab','instagram;fab','intercom;fab','internet-explorer;fab','invision;fab','ioxhost;fab','itch-io;fab','itunes;fab','itunes-note;fab','java;fab','jedi-order;fab','jenkins;fab','jira;fab','joget;fab','joomla;fab','js;fab','js-square;fab','jsfiddle;fab','kaggle;fab','keybase;fab','keycdn;fab','kickstarter;fab','kickstarter-k;fab','korvue;fab','laravel;fab','lastfm;fab','lastfm-square;fab','leanpub;fab','less;fab','line;fab','linkedin;fab','linkedin-in;fab','linode;fab','linux;fab','lyft;fab','magento;fab','mailchimp;fab','mandalorian;fab','markdown;fab','mastodon;fab','maxcdn;fab','medapps;fab','medium;fab','medium-m;fab','medrt;fab','meetup;fab','megaport;fab','mendeley;fab','microsoft;fab','mix;fab','mixcloud;fab','mizuni;fab','modx;fab','monero;fab','napster;fab','neos;fab','nimblr;fab','node;fab','node-js;fab','npm;fab','ns8;fab','nutritionix;fab','odnoklassniki;fab','odnoklassniki-square;fab','old-republic;fab','opencart;fab','openid;fab','opera;fab','optin-monster;fab','osi;fab','page4;fab','pagelines;fab','palfed;fab','patreon;fab','paypal;fab','penny-arcade;fab','periscope;fab','phabricator;fab','phoenix-framework;fab','phoenix-squadron;fab','php;fab','pied-piper;fab','pied-piper-alt;fab','pied-piper-hat;fab','pied-piper-pp;fab','pinterest;fab','pinterest-p;fab','pinterest-square;fab','playstation;fab','product-hunt;fab','pushed;fab','python;fab','qq;fab','quinscape;fab','quora;fab','r-project;fab','raspberry-pi;fab','ravelry;fab','react;fab','reacteurope;fab','readme;fab','rebel;fab','red-river;fab','reddit;fab','reddit-alien;fab','reddit-square;fab','redhat;fab','renren;fab','replyd;fab','researchgate;fab','resolving;fab','rev;fab','rocketchat;fab','rockrms;fab','safari;fab','salesforce;fab','sass;fab','schlix;fab','scribd;fab','searchengin;fab','sellcast;fab','sellsy;fab','servicestack;fab','shirtsinbulk;fab','shopware;fab','simplybuilt;fab','sistrix;fab','sith;fab','sketch;fab','skyatlas;fab','skype;fab','slack;fab','slack-hash;fab','slideshare;fab','snapchat;fab','snapchat-ghost;fab','snapchat-square;fab','soundcloud;fab','sourcetree;fab','speakap;fab','speaker-deck;fab','spotify;fab','squarespace;fab','stack-exchange;fab','stack-overflow;fab','stackpath;fab','staylinked;fab','steam;fab','steam-square;fab','steam-symbol;fab','sticker-mule;fab','strava;fab','stripe;fab','stripe-s;fab','studiovinari;fab','stumbleupon;fab','stumbleupon-circle;fab','superpowers;fab','supple;fab','suse;fab','symfony;fab','teamspeak;fab','telegram;fab','telegram-plane;fab','tencent-weibo;fab','the-red-yeti;fab','themeco;fab','themeisle;fab','think-peaks;fab','trade-federation;fab','trello;fab','tripadvisor;fab','tumblr;fab','tumblr-square;fab','twitch;fab','twitter;fab','twitter-square;fab','typo3;fab','uber;fab','ubuntu;fab','uikit;fab','uniregistry;fab','untappd;fab','ups;fab','usb;fab','usps;fab','ussunnah;fab','vaadin;fab','viacoin;fab','viadeo;fab','viadeo-square;fab','viber;fab','vimeo;fab','vimeo-square;fab','vimeo-v;fab','vine;fab','vk;fab','vnv;fab','vuejs;fab','waze;fab','weebly;fab','weibo;fab','weixin;fab','whatsapp;fab','whatsapp-square;fab','whmcs;fab','wikipedia-w;fab','windows;fab','wix;fab','wizards-of-the-coast;fab','wolf-pack-battalion;fab','wordpress;fab','wordpress-simple;fab','wpbeginner;fab','wpexplorer;fab','wpforms;fab','wpressr;fab','xbox;fab','xing;fab','xing-square;fab','y-combinator;fab','yahoo;fab','yammer;fab','yandex;fab','yandex-international;fab','yarn;fab','yelp;fab','yoast;fab','youtube;fab','youtube-square;fab','zhihu;fab'
        );

        $icon_array = array_merge($solid_icons, $regular_icons);
        $icon_array = array_merge($icon_array, $brand_icons);
        $icon_array = apply_filters( 'super_icons',  $icon_array );
        return array_unique( $icon_array );
    }    
    
}
endif;
