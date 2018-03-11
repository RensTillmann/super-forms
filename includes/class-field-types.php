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
        
    // @since 3.8.0 - field to reset submission counter for users
    public static function reset_user_submission_count( $id, $field ) {
        $return  = '<div class="input">';
            $return .= '<span class="super-button reset-user-submission-counter delete">' . __( 'Reset Submission Counter for Users', 'super-forms' ) . '</span>';
        $return .= '</div>';
        return $return;
    }

    // @since 3.4.0 - field to reset submission counter
    public static function reset_submission_count( $id, $field ) {
        $return  = '<div class="input">';
            $return .= '<input type="number" id="field-' . $id . '" name="' . $id . '" class="element-field" value="' . esc_attr( stripslashes( $field['default'] ) ) . '" />';
            $return .= '<span class="super-button reset-submission-counter delete">' . __( 'Reset Submission Counter', 'super-forms' ) . '</span>';
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
            $return .= '<select id="field-'.$id.'" name="'.$id.'" class="element-field previously-created-fields '.$multiple.'"'.$multiple.$filter.'>';
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
                $return .= '<div class="super-multi-items super-dropdown-item">';
                    if( !isset( $v['checked'] ) ) $v['checked'] = 'false';
                    $return .= '<input data-prev="'.$v['checked'].'" ' . ($id=='radio_items' || $id=='autosuggest_items' ? 'type="radio"' : 'type="checkbox"') . ( $v['checked']=='true' ? ' checked="checked"' : '' ) . '">';
                    $return .= '<div class="sorting">';
                        $return .= '<span class="up"><i class="fa fa-arrow-up"></i></span>';
                        $return .= '<span class="down"><i class="fa fa-arrow-down"></i></span>';
                    $return .= '</div>';
                    $return .= '<input type="text" placeholder="' . __( 'Label', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['label'] ) ) . '" name="label">';
                    $return .= '<input type="text" placeholder="' . __( 'Value', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['value'] ) ) . '" name="value">';
                    $return .= '<i class="add super-add-item fa fa-plus"></i>';
                    $return .= '<i class="delete fa fa-trash-o"></i>';
                    
                    // @since v1.2.3
                    if( ($id=='checkbox_items') || ($id=='radio_items') ) {
                        if( !isset( $v['image'] ) ) $v['image'] = '';
                        $return .= '<div class="image-field browse-images">';
                        $return .= '<span class="button super-insert-image"><i class="fa fa-picture-o"></i></span>';
                        $return .= '<ul class="image-preview">';
                        $image = wp_get_attachment_image_src( $v['image'], 'thumbnail' );
                        $image = !empty( $image[0] ) ? $image[0] : '';
                        if( !empty( $image ) ) {
                            $return .= '<li data-file="' . $v['image'] . '">';
                            $return .= '<div class="image"><img src="' . $image . '"></div>';
                            $return .= '<input type="number" placeholder="' . __( 'width', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['max_width'] ) ) . '" name="max_width">';
                            $return .= '<span>px</span>';
                            $return .= '<input type="number" placeholder="' . __( 'height', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['max_height'] ) ) . '" name="max_height">';
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
        $return .= '<span class="button super-insert-image"><i class="fa fa-plus"></i> ' . __( 'Browse images', 'super-forms' ) . '</span>';
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
        $return .= '<span class="button super-insert-files"><i class="fa fa-plus"></i> ' . __( 'Browse files', 'super-forms' ) . '</span>';
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

            $return .= '<input type="text" id="field-' . $id . '-field-1"';
            // get first part of placeholder
            $return .= ( $placeholders[0]!='' ? 'placeholder="' . $placeholders[0] . '"' : '' );
            $return .= 'name="' . $id . '_1" class="element-field" value="' . esc_attr( stripslashes( $defaults[0] ) ) . '" />';

            $return .= '<select name="' . $id . '_2">';
            $return .= '<option' . ($defaults[1]=='==' ? ' selected="selected"' : '') . ' value="==">== (' . __( 'Equal', 'super-forms' ) . '</option>';
            $return .= '<option' . ($defaults[1]=='!=' ? ' selected="selected"' : '') . ' value="!=">!= (' . __( 'Not equals', 'super-forms' ) . ')</option>';

            $return .= '<input type="text" id="field-' . $id . '-field-1"';
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
        $return .= 'rows="' . $field['rows'] . '" class="element-field">' . esc_textarea(stripslashes($field['default'])) . '</textarea>';
        return $return;
	}
    
    // address_auto_complete
    public static function address_auto_populate( $id, $field, $data ) {
        $mappings = array(
            'street_number' => __( 'Street number', 'super-forms' ),
            'street_name' => __( 'Street name', 'super-forms' ),
            'street_name_number' => __( 'Street name + nr', 'super-forms' ),
            'street_number_name' => __( 'Street nr + name', 'super-forms' ),
            'city' => __( 'City name', 'super-forms' ),
            'state' => __( 'State/Province', 'super-forms' ),
            'postal_code' => __( 'Postal code', 'super-forms' ),
            'country' => __( 'Country name', 'super-forms' ),
            'municipality' => __( 'Municipality', 'super-forms' )
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
                $return .= '<div class="super-multi-items super-conditional-item">';
                    $return .= '<select class="super-previously-created" name="conditional_field" data-value="' . $v['field'] . '"></select>';
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
                    $return .= '<select class="super-previously-created" name="conditional_field_and" data-value="' . $v['field_and'] . '"></select>';
                    $return .= '<select name="conditional_logic_and">';
                        $return .= '<option selected="selected" value="">---</option>';
                        foreach( $options as $ok => $ov ) {
                            $return .= '<option' . ($ok==$v['logic_and'] ? ' selected="selected"' : '') . ' value="' . $ok . '">' . $ov . '</option>';
                        }
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Value" value="' . $v['value_and'] . '" name="conditional_value_and">';
                    $return .= '<i class="add fa fa-plus"></i>';
                    $return .= '<i class="delete fa fa-trash-o" style="visibility: hidden;"></i>';
                    $return .= '<span class="line-break"></span>';
                $return .= '</div>';
            }
        }else{
            $return  = '<div class="super-multi-items super-conditional-item">';
                $return .= '<select class="super-previously-created" name="conditional_field" data-value=""></select>';
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
                $return .= '<select class="super-previously-created" name="conditional_field_and" data-value=""></select>';
                $return .= '<select name="conditional_logic_and">';
                    $return .= '<option selected="selected" value="">---</option>';
                    foreach( $options as $ok => $ov ) {
                        $return .= '<option value="' . $ok . '">' . $ov . '</option>';
                    }
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Value" value="" name="conditional_value_and">';
                $return .= '<i class="add fa fa-plus"></i>';
                $return .= '<i class="delete fa fa-trash-o" style="visibility: hidden;"></i>';
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
                $return .= '<div class="super-multi-items super-conditional-item">';
                    $return .= '<select class="super-previously-created" name="conditional_field" data-value="' . $v['field'] . '"></select>';
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
                    $return .= '<select class="super-previously-created" name="conditional_field_and" data-value="' . $v['field_and'] . '"></select>';
                    $return .= '<select name="conditional_logic_and">';
                        $return .= '<option selected="selected" value="">---</option>';
                        foreach( $options as $ok => $ov ) {
                            $return .= '<option' . ($ok==$v['logic_and'] ? ' selected="selected"' : '') . ' value="' . $ok . '">' . $ov . '</option>';
                        }
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Value" value="' . $v['value_and'] . '" name="conditional_value_and">';
                    $return .= '<i class="add fa fa-plus"></i>';
                    $return .= '<i class="delete fa fa-trash-o" style="visibility: hidden;"></i>';
                    $return .= '<span class="line-break"></span>';
                    $return .= '<p>' . __( 'When above conditions are met set following value:', 'super-forms' ) . '</p>';
                    $return .= '<textarea placeholder="New value" name="conditional_new_value">' . stripslashes( $v['new_value'] ) . '</textarea>';
                    $return .= '</div>';
            }
        }else{
            $return  = '<div class="super-multi-items super-conditional-item">';
                $return .= '<select class="super-previously-created" name="conditional_field" data-value=""></select>';
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
                $return .= '<select class="super-previously-created" name="conditional_field_and" data-value=""></select>';
                $return .= '<select name="conditional_logic_and">';
                    $return .= '<option selected="selected" value="">---</option>';
                    foreach( $options as $ok => $ov ) {
                        $return .= '<option value="' . $ok . '">' . $ov . '</option>';
                    }
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Value" value="" name="conditional_value_and">';
                $return .= '<i class="add fa fa-plus"></i>';
                $return .= '<i class="delete fa fa-trash-o" style="visibility: hidden;"></i>';
                $return .= '<span class="line-break"></span>';
                $return .= '<p>' . __( 'When above conditions are met set following value:', 'super-forms' ) . '</p>';
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
                    if( $field['default']==$k ) {
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
		$return  = '<div class="super-icon-field">';
        $icons = self::icons();
        $return .= '<div class="super-icon-search"><input type="text" placeholder="Filter icons" /></div>';
        $return .= '<div class="super-icon-list">';
        foreach($icons as $k => $v){
            if($field['default']==$v){
                 $return .= '<i class="fa fa-'.$v.' active"></i>';
            }else{
                 $return .= '<i class="fa fa-'.$v.'"></i>';
            }
        }
        $return .= '</div>';
        $return .= '<input type="hidden" name="'.$id.'" value="'.esc_attr($field['default']).'" id="field-'.$id.'" class="element-field" />';
        $return .= '</div>';
		return $return;
	
    }
    
    // Available Icons
	public static function icons() {


        /**
         * Font Awesome 4.7 icons array
         * http://fontawesome.io/cheatsheet/
         * 
         * @version 4.7.0
         * @date 14.11.2016.
         */

        $icons = array (
            0 => '500px',
            1 => 'address-book',
            2 => 'address-book-o',
            3 => 'address-card',
            4 => 'address-card-o',
            5 => 'adjust',
            6 => 'adn',
            7 => 'align-center',
            8 => 'align-justify',
            9 => 'align-left',
            10 => 'align-right',
            11 => 'amazon',
            12 => 'ambulance',
            13 => 'american-sign-language-interpreting',
            14 => 'anchor',
            15 => 'android',
            16 => 'angellist',
            17 => 'angle-double-down',
            18 => 'angle-double-left',
            19 => 'angle-double-right',
            20 => 'angle-double-up',
            21 => 'angle-down',
            22 => 'angle-left',
            23 => 'angle-right',
            24 => 'angle-up',
            25 => 'apple',
            26 => 'archive',
            27 => 'area-chart',
            28 => 'arrow-circle-down',
            29 => 'arrow-circle-left',
            30 => 'arrow-circle-o-down',
            31 => 'arrow-circle-o-left',
            32 => 'arrow-circle-o-right',
            33 => 'arrow-circle-o-up',
            34 => 'arrow-circle-right',
            35 => 'arrow-circle-up',
            36 => 'arrow-down',
            37 => 'arrow-left',
            38 => 'arrow-right',
            39 => 'arrow-up',
            40 => 'arrows',
            41 => 'arrows-alt',
            42 => 'arrows-h',
            43 => 'arrows-v',
            44 => 'asl-interpreting',
            45 => 'assistive-listening-systems',
            46 => 'asterisk',
            47 => 'at',
            48 => 'audio-description',
            49 => 'automobile',
            50 => 'backward',
            51 => 'balance-scale',
            52 => 'ban',
            53 => 'bandcamp',
            54 => 'bank',
            55 => 'bar-chart',
            56 => 'bar-chart-o',
            57 => 'barcode',
            58 => 'bars',
            59 => 'bath',
            60 => 'bathtub',
            61 => 'battery',
            62 => 'battery-0',
            63 => 'battery-1',
            64 => 'battery-2',
            65 => 'battery-3',
            66 => 'battery-4',
            67 => 'battery-empty',
            68 => 'battery-full',
            69 => 'battery-half',
            70 => 'battery-quarter',
            71 => 'battery-three-quarters',
            72 => 'bed',
            73 => 'beer',
            74 => 'behance',
            75 => 'behance-square',
            76 => 'bell',
            77 => 'bell-o',
            78 => 'bell-slash',
            79 => 'bell-slash-o',
            80 => 'bicycle',
            81 => 'binoculars',
            82 => 'birthday-cake',
            83 => 'bitbucket',
            84 => 'bitbucket-square',
            85 => 'bitcoin',
            86 => 'black-tie',
            87 => 'blind',
            88 => 'bluetooth',
            89 => 'bluetooth-b',
            90 => 'bold',
            91 => 'bolt',
            92 => 'bomb',
            93 => 'book',
            94 => 'bookmark',
            95 => 'bookmark-o',
            96 => 'braille',
            97 => 'briefcase',
            98 => 'btc',
            99 => 'bug',
            100 => 'building',
            101 => 'building-o',
            102 => 'bullhorn',
            103 => 'bullseye',
            104 => 'bus',
            105 => 'buysellads',
            106 => 'cab',
            107 => 'calculator',
            108 => 'calendar',
            109 => 'calendar-check-o',
            110 => 'calendar-minus-o',
            111 => 'calendar-o',
            112 => 'calendar-plus-o',
            113 => 'calendar-times-o',
            114 => 'camera',
            115 => 'camera-retro',
            116 => 'car',
            117 => 'caret-down',
            118 => 'caret-left',
            119 => 'caret-right',
            120 => 'caret-square-o-down',
            121 => 'caret-square-o-left',
            122 => 'caret-square-o-right',
            123 => 'caret-square-o-up',
            124 => 'caret-up',
            125 => 'cart-arrow-down',
            126 => 'cart-plus',
            127 => 'cc',
            128 => 'cc-amex',
            129 => 'cc-diners-club',
            130 => 'cc-discover',
            131 => 'cc-jcb',
            132 => 'cc-mastercard',
            133 => 'cc-paypal',
            134 => 'cc-stripe',
            135 => 'cc-visa',
            136 => 'certificate',
            137 => 'chain',
            138 => 'chain-broken',
            139 => 'check',
            140 => 'check-circle',
            141 => 'check-circle-o',
            142 => 'check-square',
            143 => 'check-square-o',
            144 => 'chevron-circle-down',
            145 => 'chevron-circle-left',
            146 => 'chevron-circle-right',
            147 => 'chevron-circle-up',
            148 => 'chevron-down',
            149 => 'chevron-left',
            150 => 'chevron-right',
            151 => 'chevron-up',
            152 => 'child',
            153 => 'chrome',
            154 => 'circle',
            155 => 'circle-o',
            156 => 'circle-o-notch',
            157 => 'circle-thin',
            158 => 'clipboard',
            159 => 'clock-o',
            160 => 'clone',
            161 => 'close',
            162 => 'cloud',
            163 => 'cloud-download',
            164 => 'cloud-upload',
            165 => 'cny',
            166 => 'code',
            167 => 'code-fork',
            168 => 'codepen',
            169 => 'codiepie',
            170 => 'coffee',
            171 => 'cog',
            172 => 'cogs',
            173 => 'columns',
            174 => 'comment',
            175 => 'comment-o',
            176 => 'commenting',
            177 => 'commenting-o',
            178 => 'comments',
            179 => 'comments-o',
            180 => 'compass',
            181 => 'compress',
            182 => 'connectdevelop',
            183 => 'contao',
            184 => 'copy',
            185 => 'copyright',
            186 => 'creative-commons',
            187 => 'credit-card',
            188 => 'credit-card-alt',
            189 => 'crop',
            190 => 'crosshairs',
            191 => 'css3',
            192 => 'cube',
            193 => 'cubes',
            194 => 'cut',
            195 => 'cutlery',
            196 => 'dashboard',
            197 => 'dashcube',
            198 => 'database',
            199 => 'deaf',
            200 => 'deafness',
            201 => 'dedent',
            202 => 'delicious',
            203 => 'desktop',
            204 => 'deviantart',
            205 => 'diamond',
            206 => 'digg',
            207 => 'dollar',
            208 => 'dot-circle-o',
            209 => 'download',
            210 => 'dribbble',
            211 => 'drivers-license',
            212 => 'drivers-license-o',
            213 => 'dropbox',
            214 => 'drupal',
            215 => 'edge',
            216 => 'edit',
            217 => 'eercast',
            218 => 'eject',
            219 => 'ellipsis-h',
            220 => 'ellipsis-v',
            221 => 'empire',
            222 => 'envelope',
            223 => 'envelope-o',
            224 => 'envelope-open',
            225 => 'envelope-open-o',
            226 => 'envelope-square',
            227 => 'envira',
            228 => 'eraser',
            229 => 'etsy',
            230 => 'eur',
            231 => 'euro',
            232 => 'exchange',
            233 => 'exclamation',
            234 => 'exclamation-circle',
            235 => 'exclamation-triangle',
            236 => 'expand',
            237 => 'expeditedssl',
            238 => 'external-link',
            239 => 'external-link-square',
            240 => 'eye',
            241 => 'eye-slash',
            242 => 'eyedropper',
            243 => 'fa',
            244 => 'facebook',
            245 => 'facebook-f',
            246 => 'facebook-official',
            247 => 'facebook-square',
            248 => 'fast-backward',
            249 => 'fast-forward',
            250 => 'fax',
            251 => 'feed',
            252 => 'female',
            253 => 'fighter-jet',
            254 => 'file',
            255 => 'file-archive-o',
            256 => 'file-audio-o',
            257 => 'file-code-o',
            258 => 'file-excel-o',
            259 => 'file-image-o',
            260 => 'file-movie-o',
            261 => 'file-o',
            262 => 'file-pdf-o',
            263 => 'file-photo-o',
            264 => 'file-picture-o',
            265 => 'file-powerpoint-o',
            266 => 'file-sound-o',
            267 => 'file-text',
            268 => 'file-text-o',
            269 => 'file-video-o',
            270 => 'file-word-o',
            271 => 'file-zip-o',
            272 => 'files-o',
            273 => 'film',
            274 => 'filter',
            275 => 'fire',
            276 => 'fire-extinguisher',
            277 => 'firefox',
            278 => 'first-order',
            279 => 'flag',
            280 => 'flag-checkered',
            281 => 'flag-o',
            282 => 'flash',
            283 => 'flask',
            284 => 'flickr',
            285 => 'floppy-o',
            286 => 'folder',
            287 => 'folder-o',
            288 => 'folder-open',
            289 => 'folder-open-o',
            290 => 'font',
            291 => 'font-awesome',
            292 => 'fonticons',
            293 => 'fort-awesome',
            294 => 'forumbee',
            295 => 'forward',
            296 => 'foursquare',
            297 => 'free-code-camp',
            298 => 'frown-o',
            299 => 'futbol-o',
            300 => 'gamepad',
            301 => 'gavel',
            302 => 'gbp',
            303 => 'ge',
            304 => 'gear',
            305 => 'gears',
            306 => 'genderless',
            307 => 'get-pocket',
            308 => 'gg',
            309 => 'gg-circle',
            310 => 'gift',
            311 => 'git',
            312 => 'git-square',
            313 => 'github',
            314 => 'github-alt',
            315 => 'github-square',
            316 => 'gitlab',
            317 => 'gittip',
            318 => 'glass',
            319 => 'glide',
            320 => 'glide-g',
            321 => 'globe',
            322 => 'google',
            323 => 'google-plus',
            324 => 'google-plus-circle',
            325 => 'google-plus-official',
            326 => 'google-plus-square',
            327 => 'google-wallet',
            328 => 'graduation-cap',
            329 => 'gratipay',
            330 => 'grav',
            331 => 'group',
            332 => 'h-square',
            333 => 'hacker-news',
            334 => 'hand-grab-o',
            335 => 'hand-lizard-o',
            336 => 'hand-o-down',
            337 => 'hand-o-left',
            338 => 'hand-o-right',
            339 => 'hand-o-up',
            340 => 'hand-paper-o',
            341 => 'hand-peace-o',
            342 => 'hand-pointer-o',
            343 => 'hand-rock-o',
            344 => 'hand-scissors-o',
            345 => 'hand-spock-o',
            346 => 'hand-stop-o',
            347 => 'handshake-o',
            348 => 'hard-of-hearing',
            349 => 'hashtag',
            350 => 'hdd-o',
            351 => 'header',
            352 => 'headphones',
            353 => 'heart',
            354 => 'heart-o',
            355 => 'heartbeat',
            356 => 'history',
            357 => 'home',
            358 => 'hospital-o',
            359 => 'hotel',
            360 => 'hourglass',
            361 => 'hourglass-1',
            362 => 'hourglass-2',
            363 => 'hourglass-3',
            364 => 'hourglass-end',
            365 => 'hourglass-half',
            366 => 'hourglass-o',
            367 => 'hourglass-start',
            368 => 'houzz',
            369 => 'html5',
            370 => 'i-cursor',
            371 => 'id-badge',
            372 => 'id-card',
            373 => 'id-card-o',
            374 => 'ils',
            375 => 'image',
            376 => 'imdb',
            377 => 'inbox',
            378 => 'indent',
            379 => 'industry',
            380 => 'info',
            381 => 'info-circle',
            382 => 'inr',
            383 => 'instagram',
            384 => 'institution',
            385 => 'internet-explorer',
            386 => 'intersex',
            387 => 'ioxhost',
            388 => 'italic',
            389 => 'joomla',
            390 => 'jpy',
            391 => 'jsfiddle',
            392 => 'key',
            393 => 'keyboard-o',
            394 => 'krw',
            395 => 'language',
            396 => 'laptop',
            397 => 'lastfm',
            398 => 'lastfm-square',
            399 => 'leaf',
            400 => 'leanpub',
            401 => 'legal',
            402 => 'lemon-o',
            403 => 'level-down',
            404 => 'level-up',
            405 => 'life-bouy',
            406 => 'life-buoy',
            407 => 'life-ring',
            408 => 'life-saver',
            409 => 'lightbulb-o',
            410 => 'line-chart',
            411 => 'link',
            412 => 'linkedin',
            413 => 'linkedin-square',
            414 => 'linode',
            415 => 'linux',
            416 => 'list',
            417 => 'list-alt',
            418 => 'list-ol',
            419 => 'list-ul',
            420 => 'location-arrow',
            421 => 'lock',
            422 => 'long-arrow-down',
            423 => 'long-arrow-left',
            424 => 'long-arrow-right',
            425 => 'long-arrow-up',
            426 => 'low-vision',
            427 => 'magic',
            428 => 'magnet',
            429 => 'mail-forward',
            430 => 'mail-reply',
            431 => 'mail-reply-all',
            432 => 'male',
            433 => 'map',
            434 => 'map-marker',
            435 => 'map-o',
            436 => 'map-pin',
            437 => 'map-signs',
            438 => 'mars',
            439 => 'mars-double',
            440 => 'mars-stroke',
            441 => 'mars-stroke-h',
            442 => 'mars-stroke-v',
            443 => 'maxcdn',
            444 => 'meanpath',
            445 => 'medium',
            446 => 'medkit',
            447 => 'meetup',
            448 => 'meh-o',
            449 => 'mercury',
            450 => 'microchip',
            451 => 'microphone',
            452 => 'microphone-slash',
            453 => 'minus',
            454 => 'minus-circle',
            455 => 'minus-square',
            456 => 'minus-square-o',
            457 => 'mixcloud',
            458 => 'mobile',
            459 => 'mobile-phone',
            460 => 'modx',
            461 => 'money',
            462 => 'moon-o',
            463 => 'mortar-board',
            464 => 'motorcycle',
            465 => 'mouse-pointer',
            466 => 'music',
            467 => 'navicon',
            468 => 'neuter',
            469 => 'newspaper-o',
            470 => 'object-group',
            471 => 'object-ungroup',
            472 => 'odnoklassniki',
            473 => 'odnoklassniki-square',
            474 => 'opencart',
            475 => 'openid',
            476 => 'opera',
            477 => 'optin-monster',
            478 => 'outdent',
            479 => 'pagelines',
            480 => 'paint-brush',
            481 => 'paper-plane',
            482 => 'paper-plane-o',
            483 => 'paperclip',
            484 => 'paragraph',
            485 => 'paste',
            486 => 'pause',
            487 => 'pause-circle',
            488 => 'pause-circle-o',
            489 => 'paw',
            490 => 'paypal',
            491 => 'pencil',
            492 => 'pencil-square',
            493 => 'pencil-square-o',
            494 => 'percent',
            495 => 'phone',
            496 => 'phone-square',
            497 => 'photo',
            498 => 'picture-o',
            499 => 'pie-chart',
            500 => 'pied-piper',
            501 => 'pied-piper-alt',
            502 => 'pied-piper-pp',
            503 => 'pinterest',
            504 => 'pinterest-p',
            505 => 'pinterest-square',
            506 => 'plane',
            507 => 'play',
            508 => 'play-circle',
            509 => 'play-circle-o',
            510 => 'plug',
            511 => 'plus',
            512 => 'plus-circle',
            513 => 'plus-square',
            514 => 'plus-square-o',
            515 => 'podcast',
            516 => 'power-off',
            517 => 'print',
            518 => 'product-hunt',
            519 => 'puzzle-piece',
            520 => 'qq',
            521 => 'qrcode',
            522 => 'question',
            523 => 'question-circle',
            524 => 'question-circle-o',
            525 => 'quora',
            526 => 'quote-left',
            527 => 'quote-right',
            528 => 'ra',
            529 => 'random',
            530 => 'ravelry',
            531 => 'rebel',
            532 => 'recycle',
            533 => 'reddit',
            534 => 'reddit-alien',
            535 => 'reddit-square',
            536 => 'refresh',
            537 => 'registered',
            538 => 'remove',
            539 => 'renren',
            540 => 'reorder',
            541 => 'repeat',
            542 => 'reply',
            543 => 'reply-all',
            544 => 'resistance',
            545 => 'retweet',
            546 => 'rmb',
            547 => 'road',
            548 => 'rocket',
            549 => 'rotate-left',
            550 => 'rotate-right',
            551 => 'rouble',
            552 => 'rss',
            553 => 'rss-square',
            554 => 'rub',
            555 => 'ruble',
            556 => 'rupee',
            557 => 's15',
            558 => 'safari',
            559 => 'save',
            560 => 'scissors',
            561 => 'scribd',
            562 => 'search',
            563 => 'search-minus',
            564 => 'search-plus',
            565 => 'sellsy',
            566 => 'send',
            567 => 'send-o',
            568 => 'server',
            569 => 'share',
            570 => 'share-alt',
            571 => 'share-alt-square',
            572 => 'share-square',
            573 => 'share-square-o',
            574 => 'shekel',
            575 => 'sheqel',
            576 => 'shield',
            577 => 'ship',
            578 => 'shirtsinbulk',
            579 => 'shopping-bag',
            580 => 'shopping-basket',
            581 => 'shopping-cart',
            582 => 'shower',
            583 => 'sign-in',
            584 => 'sign-language',
            585 => 'sign-out',
            586 => 'signal',
            587 => 'signing',
            588 => 'simplybuilt',
            589 => 'sitemap',
            590 => 'skyatlas',
            591 => 'skype',
            592 => 'slack',
            593 => 'sliders',
            594 => 'slideshare',
            595 => 'smile-o',
            596 => 'snapchat',
            597 => 'snapchat-ghost',
            598 => 'snapchat-square',
            599 => 'snowflake-o',
            600 => 'soccer-ball-o',
            601 => 'sort',
            602 => 'sort-alpha-asc',
            603 => 'sort-alpha-desc',
            604 => 'sort-amount-asc',
            605 => 'sort-amount-desc',
            606 => 'sort-asc',
            607 => 'sort-desc',
            608 => 'sort-down',
            609 => 'sort-numeric-asc',
            610 => 'sort-numeric-desc',
            611 => 'sort-up',
            612 => 'soundcloud',
            613 => 'space-shuttle',
            614 => 'spinner',
            615 => 'spoon',
            616 => 'spotify',
            617 => 'square',
            618 => 'square-o',
            619 => 'stack-exchange',
            620 => 'stack-overflow',
            621 => 'star',
            622 => 'star-half',
            623 => 'star-half-empty',
            624 => 'star-half-full',
            625 => 'star-half-o',
            626 => 'star-o',
            627 => 'steam',
            628 => 'steam-square',
            629 => 'step-backward',
            630 => 'step-forward',
            631 => 'stethoscope',
            632 => 'sticky-note',
            633 => 'sticky-note-o',
            634 => 'stop',
            635 => 'stop-circle',
            636 => 'stop-circle-o',
            637 => 'street-view',
            638 => 'strikethrough',
            639 => 'stumbleupon',
            640 => 'stumbleupon-circle',
            641 => 'subscript',
            642 => 'subway',
            643 => 'suitcase',
            644 => 'sun-o',
            645 => 'superpowers',
            646 => 'superscript',
            647 => 'support',
            648 => 'table',
            649 => 'tablet',
            650 => 'tachometer',
            651 => 'tag',
            652 => 'tags',
            653 => 'tasks',
            654 => 'taxi',
            655 => 'telegram',
            656 => 'television',
            657 => 'tencent-weibo',
            658 => 'terminal',
            659 => 'text-height',
            660 => 'text-width',
            661 => 'th',
            662 => 'th-large',
            663 => 'th-list',
            664 => 'themeisle',
            665 => 'thermometer',
            666 => 'thermometer-0',
            667 => 'thermometer-1',
            668 => 'thermometer-2',
            669 => 'thermometer-3',
            670 => 'thermometer-4',
            671 => 'thermometer-empty',
            672 => 'thermometer-full',
            673 => 'thermometer-half',
            674 => 'thermometer-quarter',
            675 => 'thermometer-three-quarters',
            676 => 'thumb-tack',
            677 => 'thumbs-down',
            678 => 'thumbs-o-down',
            679 => 'thumbs-o-up',
            680 => 'thumbs-up',
            681 => 'ticket',
            682 => 'times',
            683 => 'times-circle',
            684 => 'times-circle-o',
            685 => 'times-rectangle',
            686 => 'times-rectangle-o',
            687 => 'tint',
            688 => 'toggle-down',
            689 => 'toggle-left',
            690 => 'toggle-off',
            691 => 'toggle-on',
            692 => 'toggle-right',
            693 => 'toggle-up',
            694 => 'trademark',
            695 => 'train',
            696 => 'transgender',
            697 => 'transgender-alt',
            698 => 'trash',
            699 => 'trash-o',
            700 => 'tree',
            701 => 'trello',
            702 => 'tripadvisor',
            703 => 'trophy',
            704 => 'truck',
            705 => 'try',
            706 => 'tty',
            707 => 'tumblr',
            708 => 'tumblr-square',
            709 => 'turkish-lira',
            710 => 'tv',
            711 => 'twitch',
            712 => 'twitter',
            713 => 'twitter-square',
            714 => 'umbrella',
            715 => 'underline',
            716 => 'undo',
            717 => 'universal-access',
            718 => 'university',
            719 => 'unlink',
            720 => 'unlock',
            721 => 'unlock-alt',
            722 => 'unsorted',
            723 => 'upload',
            724 => 'usb',
            725 => 'usd',
            726 => 'user',
            727 => 'user-circle',
            728 => 'user-circle-o',
            729 => 'user-md',
            730 => 'user-o',
            731 => 'user-plus',
            732 => 'user-secret',
            733 => 'user-times',
            734 => 'users',
            735 => 'vcard',
            736 => 'vcard-o',
            737 => 'venus',
            738 => 'venus-double',
            739 => 'venus-mars',
            740 => 'viacoin',
            741 => 'viadeo',
            742 => 'viadeo-square',
            743 => 'video-camera',
            744 => 'vimeo',
            745 => 'vimeo-square',
            746 => 'vine',
            747 => 'vk',
            748 => 'volume-control-phone',
            749 => 'volume-down',
            750 => 'volume-off',
            751 => 'volume-up',
            752 => 'warning',
            753 => 'wechat',
            754 => 'weibo',
            755 => 'weixin',
            756 => 'whatsapp',
            757 => 'wheelchair',
            758 => 'wheelchair-alt',
            759 => 'wifi',
            760 => 'wikipedia-w',
            761 => 'window-close',
            762 => 'window-close-o',
            763 => 'window-maximize',
            764 => 'window-minimize',
            765 => 'window-restore',
            766 => 'windows',
            767 => 'won',
            768 => 'wordpress',
            769 => 'wpbeginner',
            770 => 'wpexplorer',
            771 => 'wpforms',
            772 => 'wrench',
            773 => 'xing',
            774 => 'xing-square',
            775 => 'y-combinator',
            776 => 'y-combinator-square',
            777 => 'yahoo',
            778 => 'yc',
            779 => 'yc-square',
            780 => 'yelp',
            781 => 'yen',
            782 => 'yoast',
            783 => 'youtube',
            784 => 'youtube-play',
            785 => 'youtube-square',
        );

		$icon_array = apply_filters( 'super_icons',  $icons );
        return array_unique( $icon_array );

	}    
    
}
endif;